<?php

namespace Modules\Playtesting\Infrastructure\GameDesign;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Application\Queries\GetGameVersions;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Playtesting\Domain\Exceptions\VersionDoesNotBelongToGame;

/**
 * The one place Playtesting reads a game's iterations.
 *
 * Every playtest names a game and a version, and the two arrive from different
 * places: the game from a resolved route binding, the version id from a
 * request body. Proving they agree is this module's foundational check, and it
 * happens here so that no command, controller or form request gets the chance
 * to skip it.
 *
 * The proof is structural rather than a comparison. A version is only ever
 * looked up *through* the game's own relation, so a version belonging to
 * somebody else's game is not found at all — there is no code path on which a
 * mismatched pair is compared, found wanting, and rejected. It simply never
 * resolves.
 *
 * Listing goes through GameDesign's published query rather than through a
 * query of its own, so the ordering a designer sees when picking a version is
 * the same ordering GameDesign uses everywhere else.
 */
final class GameCatalogue
{
    public function __construct(private readonly GetGameVersions $versions) {}

    /**
     * Resolve one of a game's versions by id, or fail.
     *
     * @throws VersionDoesNotBelongToGame when the version is not this game's
     */
    public function versionOf(Game $game, string $versionId): GameVersion
    {
        return $this->findVersionOf($game, $versionId)
            ?? throw VersionDoesNotBelongToGame::forPair($game->getKey(), $versionId);
    }

    /**
     * Resolve one of a game's versions by id, or return null.
     *
     * Used by validation, which wants to report the problem next to the field
     * rather than to raise it.
     */
    public function findVersionOf(Game $game, string $versionId): ?GameVersion
    {
        return $game->versions()->whereKey($versionId)->first();
    }

    /**
     * Determine whether a version id names one of this game's iterations.
     */
    public function gameHasVersion(Game $game, string $versionId): bool
    {
        return $game->versions()->whereKey($versionId)->exists();
    }

    /**
     * The game's iterations, newest first — what a designer picks from.
     *
     * @return Collection<int, GameVersion>
     */
    public function versionsOf(Game $game): Collection
    {
        return $this->versions->handle($game);
    }
}
