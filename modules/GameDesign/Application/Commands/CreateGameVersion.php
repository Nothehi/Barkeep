<?php

namespace Modules\GameDesign\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\GameDesign\Application\DTOs\CreateGameVersionData;
use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Events\GameVersionCreated;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Domain\ValueObjects\VersionNumber;
use Modules\Identity\Domain\Models\User;
use RuntimeException;

/**
 * Record a new iteration of a game.
 *
 * The number is allocated here and nowhere else. A caller never supplies one,
 * so there is no request that can claim v999, reuse v3, or renumber history.
 * If an import ever needs to place versions at specific numbers, that is a
 * different operation with different rules, and it should say so in its name.
 *
 * ## Getting the number right when two people press the button at once
 *
 * Allocation is read-then-write, which is a race by construction. Three
 * things make it safe, in order of how much they are relied on:
 *
 * 1. A row lock on the *game*, taken before the highest number is read. Two
 *    concurrent callers queue behind it, so the second one reads a maximum
 *    that already includes the first one's insert. On PostgreSQL this is the
 *    whole answer.
 * 2. A unique index on (game_id, version_number). Where the lock is weaker
 *    than it looks — SQLite, which the test suite runs on, ignores
 *    `FOR UPDATE` entirely — the database still refuses the duplicate.
 * 3. A bounded retry. A caller whose insert lost the race re-reads and takes
 *    the next number instead of failing, so losing a race costs a round trip
 *    rather than an error page.
 *
 * The retry limit exists so a genuine, repeating fault surfaces as an
 * exception rather than as a loop that never ends.
 */
final class CreateGameVersion
{
    /**
     * How many times to re-read and try again after losing a race.
     */
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly GameModificationGuard $guard) {}

    public function handle(User $creator, Game $game, CreateGameVersionData $data): GameVersion
    {
        $this->guard->ensureGameIsModifiable($game);

        $version = $this->allocate($creator, $game, $data);

        $version->setRelation('game', $game);

        event(new GameVersionCreated(
            versionId: $version->id,
            gameId: $game->id,
            workspaceId: $game->workspace_id,
            versionNumber: $version->version_number,
            createdBy: $creator->id,
        ));

        return $version;
    }

    /**
     * Take the next free number and write the version under it.
     */
    private function allocate(User $creator, Game $game, CreateGameVersionData $data): GameVersion
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(function () use ($creator, $game, $data): GameVersion {
                    /**
                     * Lock the game rather than the versions. There is no row
                     * to lock for a version that does not exist yet, and the
                     * game is the thing whose "highest version" is being
                     * read and then depended on.
                     */
                    Game::query()
                        ->whereKey($game->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    $next = $this->nextNumberFor($game);

                    $version = new GameVersion;

                    $version->fill([
                        'name' => $data->name,
                        'description' => $data->description,
                    ]);

                    $version->game_id = $game->getKey();
                    $version->version_number = $next->value;
                    $version->created_by = $creator->id;

                    $version->save();

                    return $version;
                });
            } catch (UniqueConstraintViolationException) {
                /**
                 * Somebody else took this number in the window the lock did
                 * not cover. Nothing is wrong with the request — go round
                 * again and read the number they left behind.
                 */
                continue;
            }
        }

        throw new RuntimeException(
            "Could not allocate a version number for game [{$game->getKey()}] after ".self::MAX_ATTEMPTS.' attempts.',
        );
    }

    /**
     * The number that follows the game's highest existing version.
     */
    private function nextNumberFor(Game $game): VersionNumber
    {
        $highest = $game->versions()->max('version_number');

        return $highest === null
            ? VersionNumber::first()
            : VersionNumber::fromInt((int) $highest)->next();
    }
}
