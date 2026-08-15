<?php

namespace Modules\GameDesign\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Application\DTOs\GameFilters;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;
use Modules\GameDesign\Domain\ValueObjects\VersionNumber;
use Modules\GameDesign\Infrastructure\Search\GameSearchTerm;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Every read the module performs against its own tables.
 *
 * Collecting them here is what makes "a game is only ever visible inside its
 * workspace" checkable: there is one method that lists games, it takes a
 * workspace, and no query elsewhere gets the chance to forget the scope.
 */
final class GameRepository
{
    /**
     * The games in a workspace, newest activity first.
     *
     * The workspace is a parameter rather than a filter, so there is no way
     * to call this without one. Ordering is by last change because the games
     * screen is a working list — the project somebody touched this morning
     * belongs at the top, not the one named "Aardvark".
     *
     * @return Collection<int, Game>
     */
    public function forWorkspace(Workspace $workspace, ?GameFilters $filters = null): Collection
    {
        $filters ??= GameFilters::none();

        $games = Game::query()
            ->where('workspace_id', $workspace->getKey())
            ->when(
                $filters->status !== null,
                fn ($query) => $query->where('status', $filters->status),
            )
            ->when(
                $filters->designPhase !== null,
                fn ($query) => $query->where('design_phase', $filters->designPhase),
            )
            ->when(
                $filters->search !== null,
                fn ($query) => $this->applySearch($query, $filters->search),
            )
            ->withCount('versions')
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->get();

        return $this->withWorkspace($workspace, $games);
    }

    /**
     * Find a game by its address within a workspace.
     *
     * Deliberately unauthorized: resolving a game and deciding who may see it
     * are separate steps, and every caller runs the policy on the result.
     * Scoping to the workspace is not part of that separation, though — it is
     * how the address is identified at all, since two workspaces may each
     * have a game at the same one.
     */
    public function findBySlug(Workspace $workspace, GameSlug $slug): ?Game
    {
        $game = Game::query()
            ->where('workspace_id', $workspace->getKey())
            ->where('slug', $slug->value)
            ->first();

        return $game === null ? null : $this->attachWorkspace($workspace, $game);
    }

    /**
     * Determine whether an address is already used in a workspace.
     *
     * @param  string|null  $exceptGameId  the game allowed to keep its own address
     */
    public function slugExists(Workspace $workspace, GameSlug $slug, ?string $exceptGameId = null): bool
    {
        return Game::query()
            ->where('workspace_id', $workspace->getKey())
            ->where('slug', $slug->value)
            ->when($exceptGameId !== null, fn ($query) => $query->whereKeyNot($exceptGameId))
            ->exists();
    }

    /**
     * A game's iterations, newest first.
     *
     * Ordered by version number rather than by creation time: the number is
     * what the domain guarantees is unique and ordered, and two versions cut
     * in the same second must still come back in a stable order.
     *
     * @return Collection<int, GameVersion>
     */
    public function versionsOf(Game $game): Collection
    {
        return $game->versions()
            ->with('creator')
            ->orderByDesc('version_number')
            ->get();
    }

    /**
     * Find one iteration of a game by its number.
     */
    public function findVersion(Game $game, VersionNumber $number): ?GameVersion
    {
        return $game->versions()
            ->where('version_number', $number->value)
            ->with('creator')
            ->first();
    }

    /**
     * A game's current iteration, if it has any.
     */
    public function latestVersionOf(Game $game): ?GameVersion
    {
        return $game->versions()
            ->with('creator')
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * How many iterations a game has been through.
     */
    public function countVersionsOf(Game $game): int
    {
        return $game->versions()->count();
    }

    /**
     * Match a term against a game's name and description.
     *
     * Case folded on both sides so that searching "bears" finds "Bears &
     * Bridges" on every database the application runs on, rather than only on
     * the ones whose collation happens to be insensitive.
     *
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    private function applySearch(Builder $query, GameSearchTerm $term): Builder
    {
        $pattern = $term->pattern();
        $escape = GameSearchTerm::ESCAPE;

        return $query->where(function ($query) use ($pattern, $escape) {
            $query
                ->whereRaw('LOWER(name) LIKE ? ESCAPE ?', [$pattern, $escape])
                ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ? ESCAPE ?", [$pattern, $escape]);
        });
    }

    /**
     * Give every game in a workspace-scoped list the same workspace object.
     *
     * Each game is rendered with what the caller may do to it, and every one
     * of those answers needs the caller's membership of the workspace. Left
     * alone, Eloquent would lazily load a separate workspace per game and
     * resolve the same membership once per game.
     *
     * They all belong to the workspace that was passed in — that is what
     * scoping the query means — so handing them the one instance already in
     * hand collapses the whole list onto a single membership lookup, which
     * the workspace model then memoises.
     *
     * @param  Collection<int, Game>  $games
     * @return Collection<int, Game>
     */
    private function withWorkspace(Workspace $workspace, Collection $games): Collection
    {
        return $games->each(fn (Game $game) => $this->attachWorkspace($workspace, $game));
    }

    /**
     * Hand a game the workspace instance it was resolved through.
     */
    private function attachWorkspace(Workspace $workspace, Game $game): Game
    {
        return $game->setRelation('workspace', $workspace);
    }
}
