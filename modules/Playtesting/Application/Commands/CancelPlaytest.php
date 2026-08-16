<?php

namespace Modules\Playtesting\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Events\PlaytestCancelled;
use Modules\Playtesting\Domain\Exceptions\InvalidPlaytestTransition;
use Modules\Playtesting\Domain\Models\Playtest;

/**
 * Call a playtest off.
 *
 * The honest ending for an investigation that did not happen, or that stopped
 * being worth running. Cancelling keeps the record — what somebody intended to
 * find out is itself worth knowing later — while making clear that no answer
 * came of it.
 *
 * Sessions are deliberately left alone. A playtest can be cancelled with two
 * completed sittings behind it, and those sittings really did happen; blanking
 * them would destroy evidence to tidy up a status. What the playtest concluded
 * is what was cancelled, not what was observed.
 *
 * A completed playtest cannot be cancelled. Reclassifying a finished
 * investigation would make the record of what was concluded, and when,
 * unreliable.
 */
final class CancelPlaytest
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $actor, Playtest $playtest): Playtest
    {
        $this->guard->ensurePlaytestIsModifiable($playtest);

        $cancelledAt = now()->toImmutable();

        DB::transaction(function () use ($playtest): void {
            $fresh = Playtest::query()
                ->whereKey($playtest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(PlaytestStatus::Cancelled)) {
                throw InvalidPlaytestTransition::between($fresh->status, PlaytestStatus::Cancelled);
            }

            $fresh->forceFill(['status' => PlaytestStatus::Cancelled])->save();

            $playtest->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new PlaytestCancelled(
            playtestId: $playtest->id,
            gameId: $playtest->game_id,
            gameVersionId: $playtest->game_version_id,
            cancelledBy: $actor->id,
            cancelledAt: $cancelledAt->toDateTimeImmutable(),
        ));

        return $playtest;
    }
}
