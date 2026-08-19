<?php

namespace Modules\PrototypeIteration\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Domain\Exceptions\PrototypeVersionDoesNotBelongToGame;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * The one place a prototype state is resolved against the game that owns it.
 *
 * The mirror image of `GameCatalogue`, for this module's own tables. An iteration
 * names a game and a prototype version, and the two arrive from different places:
 * the game from a resolved route binding, the version id from a request body.
 * Proving they agree is the other half of the module's central invariant, and it
 * happens here so that no command, controller or form request gets the chance to
 * skip it.
 *
 * The proof is structural rather than a comparison. A version is only ever looked
 * up *through* the game's own prototypes, so a version belonging to somebody
 * else's game is not found and then rejected — it never resolves. That
 * distinction matters more here than for game versions, because a mismatched
 * *prototype* version is invisible outside this module: nothing else in the
 * platform would notice an iteration pointing at another project's build, and the
 * record would read perfectly while describing work nobody did.
 *
 * A service rather than an adapter, because these are this module's own records.
 * It sits in the application layer for the same reason `GameCatalogue` sits in
 * infrastructure: each is placed where the thing it speaks for lives.
 */
final class PrototypeCatalogue
{
    public function __construct(private readonly PrototypeRepository $prototypes) {}

    /**
     * Resolve one of a game's prototype states by id, or fail.
     *
     * @throws PrototypeVersionDoesNotBelongToGame when the version is not this
     *                                             game's
     */
    public function versionOf(Game $game, string $prototypeVersionId): PrototypeVersion
    {
        return $this->findVersionOf($game, $prototypeVersionId)
            ?? throw PrototypeVersionDoesNotBelongToGame::forPair($game->getKey(), $prototypeVersionId);
    }

    /**
     * Resolve one of a game's prototype states by id, or return null.
     *
     * Used by validation, which wants to report the problem next to the field
     * rather than to raise it.
     */
    public function findVersionOf(Game $game, string $prototypeVersionId): ?PrototypeVersion
    {
        return $this->prototypes->findVersionOfGame($game, $prototypeVersionId);
    }

    /**
     * Determine whether a prototype version id names one of this game's builds.
     */
    public function gameHasVersion(Game $game, string $prototypeVersionId): bool
    {
        return $this->prototypes->gameHasVersion($game, $prototypeVersionId);
    }

    /**
     * Every prototype state in the game, as the iteration form's picker sees it.
     *
     * Loaded with its prototype, because a bare "v4" means nothing when a game has
     * three prototypes on the go — the picker has to read "Core Combat Prototype ·
     * v4" or a designer will pick the wrong build.
     *
     * @return Collection<int, PrototypeVersion>
     */
    public function selectableVersionsOf(Game $game): Collection
    {
        return $this->prototypes->selectableVersionsOf($game);
    }

    /**
     * The prototypes of a game, for filtering an iteration list by build.
     *
     * @return Collection<int, Prototype>
     */
    public function prototypesOf(Game $game): Collection
    {
        return $this->prototypes->forGame($game);
    }
}
