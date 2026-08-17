<?php

namespace Modules\GameDesign\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameDesign\Application\DTOs\DesignRecordData;
use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Events\DesignRecordUpdated;
use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Exceptions\UnknownMechanic;
use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\MechanicRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record what has been decided about a game's design.
 *
 * The record is created on first save rather than alongside the game, so a
 * project nobody has made decisions about carries no row. That absence is
 * meaningful — it is the difference between "not decided" and "decided to leave
 * blank" — and it is why this is `firstOrNew` rather than an assumption that a
 * record exists.
 *
 * ## Replacement, not patch
 *
 * A field absent from the submission is cleared. That matches the game's own
 * update and is the only honest reading of a form that always sends every field:
 * a designer who deletes their pitch means the pitch is gone, and a patch would
 * make deleting anything impossible.
 *
 * ## The mechanics are checked, not trusted
 *
 * Ids arrive from a picker, and a picker can be stale — a term may have been
 * retired by a curator since the page loaded. Every id is resolved against the
 * vocabulary and the whole submission is refused if any of them cannot be
 * claimed, rather than silently dropping the ones that cannot. A designer who
 * saves five mechanics and gets four back has been told nothing.
 *
 * Terms already claimed are unaffected by retirement; only newly claiming one is
 * refused. That is what `MechanicRepository::findMany()` excluding archived rows
 * buys, and it is why archiving a mechanic cannot rewrite anybody's record.
 */
final class UpdateDesignRecord
{
    /**
     * The prose and numeric fields, mapped from the data object to the column
     * they are stored in. Listed once so that adding a field is one line rather
     * than three.
     *
     * @var array<string, string>
     */
    private const FIELDS = [
        'pitch' => 'pitch',
        'audience' => 'audience',
        'coreAction' => 'core_action',
        'coreCost' => 'core_cost',
        'coreReward' => 'core_reward',
        'winCondition' => 'win_condition',
        'failureCondition' => 'failure_condition',
    ];

    public function __construct(
        private readonly GameModificationGuard $guard,
        private readonly MechanicRepository $mechanics,
    ) {}

    /**
     * @throws GameIsNotModifiable
     * @throws UnknownMechanic
     */
    public function handle(User $actor, Game $game, DesignRecordData $data): DesignRecord
    {
        $this->guard->ensureGameIsModifiable($game);

        $claimed = $this->resolveMechanics($data->mechanicIds);

        $record = DesignRecord::query()->firstOrNew(['game_id' => $game->getKey()]);

        foreach (self::FIELDS as $property => $column) {
            $record->setAttribute($column, $data->{$property});
        }

        $record->player_count_min = $data->playerCount?->min;
        $record->player_count_max = $data->playerCount?->max;
        $record->play_time_min = $data->playTime?->min;
        $record->play_time_max = $data->playTime?->max;
        $record->target_age_min = $data->targetAgeMin;
        $record->complexity = $data->complexity;

        $changed = $this->changedFields($record);

        /*
         * The mechanics are compared before the sync rather than after, because
         * `sync()` reports what it did in terms of attach and detach lists and
         * all this needs to know is whether the set moved at all.
         */
        $mechanicsChanged = $record->exists
            ? $this->selectionChanged($record, $claimed)
            : $claimed !== [];

        /*
         * Nothing decided means nothing stored. A submission of empty boxes
         * against a game that has no record leaves it with no record, rather
         * than creating a row that says nothing — the absence is what a
         * methodology reads as "not decided yet", and it should not be possible
         * to destroy that by opening the form and pressing save.
         */
        if ($changed === [] && ! $mechanicsChanged) {
            return $record;
        }

        DB::transaction(function () use ($record, $game, $claimed): void {
            $record->game_id = $game->getKey();
            $record->save();
            $record->mechanics()->sync($claimed);
        });

        $record->setRelation('game', $game);
        $record->load('mechanics');

        if ($mechanicsChanged) {
            $changed[] = 'mechanics';
        }

        event(new DesignRecordUpdated(
            designRecordId: $record->id,
            gameId: $game->getKey(),
            workspaceId: $game->workspace_id,
            updatedBy: $actor->id,
            changed: array_values($changed),
        ));

        return $record;
    }

    /**
     * The fields whose answers actually moved.
     *
     * Eloquent reports every attribute of an unsaved model as dirty, including
     * the ones set to null, because there is no stored value to compare against.
     * That is the wrong answer for a record: a field nobody filled in has not
     * changed, it has never been decided, and reporting it would make the first
     * save of a player count announce that the pitch changed too.
     *
     * On a record that already exists the opposite holds — a field going from
     * something to null is a real decision to forget it, and stays in the list.
     *
     * `game_id` is excluded throughout. It is how the record was found rather
     * than something anybody decided.
     *
     * @return list<string>
     */
    private function changedFields(DesignRecord $record): array
    {
        $dirty = $record->getDirty();

        unset($dirty['game_id']);

        if (! $record->exists) {
            $dirty = array_filter($dirty, fn (mixed $value): bool => $value !== null);
        }

        return array_keys($dirty);
    }

    /**
     * Prove that every submitted id names a term this game may claim.
     *
     * @param  list<string>  $ids
     * @return list<string>
     *
     * @throws UnknownMechanic
     */
    private function resolveMechanics(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $found = $this->mechanics->findMany($ids);

        $missing = array_values(array_filter(
            $ids,
            fn (string $id): bool => ! $found->has($id),
        ));

        if ($missing !== []) {
            throw UnknownMechanic::forIds($missing);
        }

        return array_values($found->map(
            fn (Mechanic $mechanic): string => (string) $mechanic->getKey(),
        )->all());
    }

    /**
     * Determine whether the set of claimed terms is different from the stored one.
     *
     * @param  list<string>  $claimed
     */
    private function selectionChanged(DesignRecord $record, array $claimed): bool
    {
        $current = $record->mechanics()
            ->pluck('mechanics.id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        sort($current);
        sort($claimed);

        return $current !== $claimed;
    }
}
