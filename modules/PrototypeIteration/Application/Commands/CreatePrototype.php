<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\CreatePrototypeData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Events\PrototypeCreated;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Infrastructure\GameDesign\GameCatalogue;

/**
 * Start something buildable from a specific state of a game's design.
 *
 * Half the module's central invariant is established here: the game arrives as a
 * resolved route binding, the design version arrives as an id in a request body,
 * and the version is looked up *through* the game's own iterations. A version
 * belonging to somebody else's game is not compared and rejected — it simply does
 * not resolve.
 *
 * The creator is the signed in account rather than anything the caller sent. Every
 * field in this module that identifies a person or a boundary comes from the
 * request context, never from its body.
 *
 * A prototype starts as a draft with no versions. The alternative — cutting v1
 * automatically — was rejected because a prototype and its first build are
 * different acts separated by real time: somebody creates "Core Combat Prototype"
 * when they decide to try the approach, and cuts v1 when the cards come out of the
 * printer. An auto-created v1 would be a version nobody built, and the module's
 * whole immutability arrangement rests on versions meaning something.
 */
final class CreatePrototype
{
    public function __construct(
        private readonly GameCatalogue $catalogue,
        private readonly DesignWorkGuard $guard,
    ) {}

    public function handle(User $creator, Game $game, CreatePrototypeData $data): Prototype
    {
        $this->guard->ensureGameAcceptsDesignWork($game);

        $version = $this->catalogue->versionOf($game, $data->gameVersionId);

        $prototype = new Prototype;

        $prototype->fill([
            'name' => $data->name,
            'description' => $data->description,
        ]);

        $prototype->game_id = $game->getKey();
        $prototype->game_version_id = $version->getKey();
        $prototype->type = $data->type;
        $prototype->status = PrototypeStatus::default();
        $prototype->created_by = $creator->id;

        $prototype->save();

        /*
         * Hand back the objects already in hand rather than letting the caller
         * lazily reload them. The game in particular carries a memoised workspace
         * membership, which every permission answer on the way out is about to
         * need.
         */
        $prototype->setRelation('game', $game);
        $prototype->setRelation('version', $version);
        $prototype->setRelation('creator', $creator);

        event(new PrototypeCreated(
            prototypeId: $prototype->id,
            gameId: $game->getKey(),
            gameVersionId: $version->getKey(),
            type: $prototype->type,
            createdBy: $creator->id,
        ));

        return $prototype;
    }
}
