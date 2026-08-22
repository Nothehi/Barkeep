<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Shared ground for the sample studio seeders.
 *
 * The sample data is written across one seeder per bounded context, in the
 * order the studio itself would have produced it: accounts and workspaces, then
 * games, then the methodology they follow, then the playtests, then the
 * prototypes and iterations those playtests fed, then the economies. Each of
 * those files is runnable on its own, which is only possible because none of
 * them holds a reference to another's rows — they look each other's records up
 * by address, exactly as a person would.
 *
 * That is what these helpers are for. `user()`, `workspace()`, `game()` and
 * `version()` are the four addresses the sample data is keyed by, and a
 * seeder that asks for one that has not been written yet fails loudly here
 * rather than three tables later with a null foreign key.
 */
abstract class SampleSeeder extends Seeder
{
    /**
     * Find a sample account by its address.
     */
    protected function user(string $email): User
    {
        $user = User::query()->where('email', $email)->first();

        return $user ?? throw new \RuntimeException(
            "Sample account [{$email}] is missing. Run SampleStudioSeeder first."
        );
    }

    /**
     * Find a sample workspace by its address.
     */
    protected function workspace(string $slug): Workspace
    {
        $workspace = Workspace::query()->where('slug', $slug)->first();

        return $workspace ?? throw new \RuntimeException(
            "Sample workspace [{$slug}] is missing. Run SampleStudioSeeder first."
        );
    }

    /**
     * Find a sample game by its address.
     *
     * Slugs are unique per workspace rather than globally, so the workspace is
     * part of the address here for the same reason it is part of the URL.
     */
    protected function game(string $workspaceSlug, string $slug): Game
    {
        $game = Game::query()
            ->where('workspace_id', $this->workspace($workspaceSlug)->getKey())
            ->where('slug', $slug)
            ->first();

        return $game ?? throw new \RuntimeException(
            "Sample game [{$workspaceSlug}/{$slug}] is missing. Run SampleGameSeeder first."
        );
    }

    /**
     * Find one of a game's design versions by its number.
     */
    protected function version(Game $game, int $number): GameVersion
    {
        $version = $game->versions()->where('version_number', $number)->first();

        return $version ?? throw new \RuntimeException(
            "Sample game [{$game->slug}] has no v{$number}. Run SampleGameSeeder first."
        );
    }

    /**
     * A point in the studio's recent past, as a whole hour.
     *
     * Every date in the sample data is expressed as "so many days ago" rather
     * than as a literal, so a database seeded today reads as a studio that has
     * been working for two years rather than one that stopped in 2026.
     */
    protected function daysAgo(int $days, int $hour = 10): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfDay()->subDays($days)->setTime($hour, 0);
    }

    /**
     * A point in the studio's near future, as a whole hour.
     *
     * Planned sessions and outstanding invitations need a date that is still
     * ahead of whoever is looking at the screen, or the sample data arrives
     * already overdue.
     */
    protected function daysAhead(int $days, int $hour = 19): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfDay()->addDays($days)->setTime($hour, 0);
    }

    /**
     * Stamp a record's own history onto it.
     *
     * Almost every list in the application is ordered by `created_at`, so rows
     * written in one pass would otherwise all share a timestamp and sort
     * arbitrarily — which is exactly the thing that makes seeded data read as
     * seeded data.
     *
     * Written through the model's own timestamp columns rather than by name,
     * because not every model has both: a balance snapshot is immutable and has
     * no `updated_at` to set.
     */
    protected function stamp(Model $model, CarbonImmutable $createdAt, ?CarbonImmutable $updatedAt = null): void
    {
        $model->setAttribute($model->getCreatedAtColumn(), $createdAt);

        $updatedColumn = $model->getUpdatedAtColumn();

        if ($updatedColumn !== null) {
            $model->setAttribute($updatedColumn, $updatedAt ?? $createdAt);
        }
    }
}
