<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\CreateIterationData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Application\Services\PrototypeCatalogue;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Events\IterationCreated;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\GameDesign\GameCatalogue;

/**
 * Plan a turn of the design loop.
 *
 * The module's central invariant is established here in full, and this is the one
 * command where both halves of it apply. Three things have to name the same project:
 * the game arrives as a resolved route binding, the design version and the prototype
 * version arrive as ids in a request body, and each is looked up *through* the game
 * — the design version through GameDesign's own relation, the prototype version
 * through the game's own prototypes.
 *
 * Neither is compared and rejected. A version belonging to somebody else's game
 * simply does not resolve, which is a stronger guarantee than a check somebody could
 * forget to write.
 *
 * It matters more than it sounds, and it matters most for the prototype version. A
 * mismatched *design* version would be caught by GameDesign the moment anything
 * looked at it; a mismatched prototype version is this module's own record, so
 * nothing else in the platform would notice. The iteration would read perfectly,
 * appear in the right list, and describe a cycle nobody ran against a build from a
 * different studio's game. There is a test that attempts exactly that forgery.
 *
 * The creator is the signed in account rather than anything the caller sent, and the
 * status is not an input: every iteration starts planned, with nothing yet to say
 * about how it went.
 */
final class CreateIteration
{
    public function __construct(
        private readonly GameCatalogue $games,
        private readonly PrototypeCatalogue $prototypes,
        private readonly DesignWorkGuard $guard,
    ) {}

    public function handle(User $creator, Game $game, CreateIterationData $data): Iteration
    {
        $this->guard->ensureGameAcceptsDesignWork($game);

        $version = $this->games->versionOf($game, $data->gameVersionId);
        $prototypeVersion = $this->prototypes->versionOf($game, $data->prototypeVersionId);

        $iteration = new Iteration;

        $iteration->fill([
            'title' => $data->title,
            'objective' => $data->objective,
            'hypothesis' => $data->hypothesis,
        ]);

        $iteration->game_id = $game->getKey();
        $iteration->game_version_id = $version->getKey();
        $iteration->prototype_version_id = $prototypeVersion->getKey();
        $iteration->status = IterationStatus::default();
        $iteration->created_by = $creator->id;

        $iteration->save();

        /*
         * Hand back the objects already in hand rather than letting the caller
         * lazily reload them. The game in particular carries a memoised workspace
         * membership, which every permission answer on the way out is about to need.
         */
        $iteration->setRelation('game', $game);
        $iteration->setRelation('version', $version);
        $iteration->setRelation('prototypeVersion', $prototypeVersion);
        $iteration->setRelation('creator', $creator);

        event(new IterationCreated(
            iterationId: $iteration->id,
            gameId: $game->getKey(),
            gameVersionId: $version->getKey(),
            prototypeVersionId: $prototypeVersion->getKey(),
            createdBy: $creator->id,
        ));

        return $iteration;
    }
}
