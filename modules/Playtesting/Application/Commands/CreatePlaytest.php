<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\CreatePlaytestData;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Events\PlaytestCreated;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Infrastructure\GameDesign\GameCatalogue;

/**
 * Plan a playtest against a specific version of a game.
 *
 * The module's central invariant is established here and nowhere else: the
 * game arrives as a resolved route binding, the version arrives as an id in a
 * request body, and the version is looked up *through* the game's own
 * iterations. A version belonging to somebody else's game is not compared and
 * rejected — it simply does not resolve.
 *
 * That matters more than it sounds. A playtest whose version came from a
 * different game is not a validation error; it is a record that reads
 * perfectly and describes an evening that never happened.
 *
 * The creator is the signed in account rather than anything the caller sent.
 * Every field in this module that identifies a person or a boundary comes from
 * the request context, never from its body.
 */
final class CreatePlaytest
{
    public function __construct(
        private readonly GameCatalogue $catalogue,
        private readonly PlaytestModificationGuard $guard,
    ) {}

    public function handle(User $creator, Game $game, CreatePlaytestData $data): Playtest
    {
        $this->guard->ensureGameAcceptsPlaytests($game);

        $version = $this->catalogue->versionOf($game, $data->gameVersionId);

        $playtest = new Playtest;

        $playtest->fill([
            'title' => $data->title,
            'objective' => $data->objective,
            'hypothesis' => $data->hypothesis,
        ]);

        $playtest->game_id = $game->getKey();
        $playtest->game_version_id = $version->getKey();
        $playtest->status = PlaytestStatus::default();
        $playtest->planned_at = $data->plannedAt;
        $playtest->created_by = $creator->id;

        $playtest->save();

        /*
         * Hand back the objects already in hand rather than letting the
         * caller lazily reload them. The game in particular carries a
         * memoised workspace membership, which every permission answer on the
         * way out is about to need.
         */
        $playtest->setRelation('game', $game);
        $playtest->setRelation('version', $version);
        $playtest->setRelation('creator', $creator);

        event(new PlaytestCreated(
            playtestId: $playtest->id,
            gameId: $game->getKey(),
            gameVersionId: $version->getKey(),
            createdBy: $creator->id,
        ));

        return $playtest;
    }
}
