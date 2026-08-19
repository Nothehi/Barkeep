<?php

namespace Modules\PrototypeIteration\Infrastructure\GameDesign;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Application\Commands\CreateGameVersion;
use Modules\GameDesign\Application\DTOs\CreateGameVersionData;
use Modules\GameDesign\Application\Queries\GetGameVersions;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Exceptions\VersionDoesNotBelongToGame;

/**
 * The one place PrototypeIteration touches a game's design states.
 *
 * Prototypes and iterations both name a game and a game version, and the two
 * arrive from different places: the game from a resolved route binding, the
 * version id from a request body. Proving they agree is half of this module's
 * foundational check, and it happens here so no command, controller or form
 * request gets the chance to skip it.
 *
 * The proof is structural rather than a comparison. A version is only ever
 * looked up *through* the game's own relation, so a version belonging to
 * somebody else's game is not found at all — there is no code path on which a
 * mismatched pair is compared, found wanting and rejected. It simply never
 * resolves.
 *
 * Listing and creating both go through GameDesign's published command and query
 * rather than through queries of their own, so the ordering a designer sees when
 * picking a version is GameDesign's ordering, and a version cut from here is
 * numbered by GameDesign's own allocator. An architecture test holds the line:
 * nothing else in this module reaches for a game's versions.
 */
final class GameCatalogue
{
    public function __construct(
        private readonly GetGameVersions $versions,
        private readonly CreateGameVersion $createVersion,
    ) {}

    /**
     * Resolve one of a game's design states by id, or fail.
     *
     * @throws VersionDoesNotBelongToGame when the version is not this game's
     */
    public function versionOf(Game $game, string $versionId): GameVersion
    {
        return $this->findVersionOf($game, $versionId)
            ?? throw VersionDoesNotBelongToGame::forPair($game->getKey(), $versionId);
    }

    /**
     * Resolve one of a game's design states by id, or return null.
     *
     * Used by validation, which wants to report the problem next to the field
     * rather than to raise it.
     */
    public function findVersionOf(Game $game, string $versionId): ?GameVersion
    {
        return $game->versions()->whereKey($versionId)->first();
    }

    /**
     * Determine whether a version id names one of this game's design states.
     */
    public function gameHasVersion(Game $game, string $versionId): bool
    {
        return $game->versions()->whereKey($versionId)->exists();
    }

    /**
     * The game's design states, newest first — what a designer picks from.
     *
     * @return Collection<int, GameVersion>
     */
    public function versionsOf(Game $game): Collection
    {
        return $this->versions->handle($game);
    }

    /**
     * The game's newest design state, or null when it has none yet.
     *
     * The default a new prototype or iteration is offered, because "the current
     * design" is what a designer means when they do not say otherwise.
     */
    public function latestVersionOf(Game $game): ?GameVersion
    {
        return $this->versionsOf($game)->first();
    }

    /**
     * Cut the next design state of a game, on a designer's explicit say-so.
     *
     * The deliberate seam described in section 48, and the reason this adapter
     * holds a command as well as a query. When an iteration concludes that the
     * design has genuinely moved on, the designer may say so from the iteration
     * screen — but the version is created *by GameDesign*, numbered by its
     * allocator, guarded by its rules and announced by its event.
     *
     * Nothing in this module calls this automatically. Completing an iteration
     * does not cut a version, and it must not: whether a cycle's conclusions
     * amount to a new design state is a judgement, and taking it away from the
     * designer would fill a game's history with versions nobody meant.
     */
    public function createNextVersionOf(User $creator, Game $game, ?string $name = null, ?string $description = null): GameVersion
    {
        return $this->createVersion->handle($creator, $game, new CreateGameVersionData(
            name: $name,
            description: $description,
        ));
    }
}
