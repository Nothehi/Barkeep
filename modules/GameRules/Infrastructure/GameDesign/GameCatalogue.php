<?php

namespace Modules\GameRules\Infrastructure\GameDesign;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Application\Queries\GetGameVersions;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Exceptions\VersionDoesNotBelongToGame;

/**
 * The one place GameRules touches a game's design states.
 *
 * Everything in this module hangs off a `GameVersion`, and the version arrives
 * from a different place than the game does: the game from a resolved route
 * binding, the version from the segment after it or from a request body. Proving
 * they agree is this module's foundational check, and it happens here so that no
 * command, controller or form request gets the chance to skip it.
 *
 * The proof is structural rather than a comparison. A version is only ever looked
 * up *through* the game's own relation, so a version belonging to somebody else's
 * game is not found at all — there is no code path on which a mismatched pair is
 * compared, found wanting and rejected.
 *
 * Listing goes through GameDesign's published query rather than through a query
 * of its own, so the ordering a designer sees when picking a version is
 * GameDesign's ordering. An architecture test holds the line: nothing else in
 * this module reaches for a game's versions.
 *
 * Note what is deliberately absent. This adapter has no way to *create* a
 * version, unlike PrototypeIteration's namesake, which cuts one when a designer
 * says the design has moved on. Writing rules is not a design change and must not
 * produce one — and cloning a rule set makes a new set on the *same* version,
 * which is the whole point of section 55 of the brief.
 */
final class GameCatalogue
{
    public function __construct(private readonly GetGameVersions $versions) {}

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
        $version = $game->versions()->whereKey($versionId)->first();

        return $version === null ? null : $version->setRelation('game', $game);
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
     * The game a design state belongs to.
     *
     * The reverse direction, and the only one in the module: a rule set knows its
     * version, and the policy needs the game that version belongs to in order to
     * decide anything at all.
     */
    public function gameOfVersion(GameVersion $version): ?Game
    {
        return $version->game;
    }
}
