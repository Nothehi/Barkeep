<?php

namespace Modules\GameDesign\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Application\DTOs\GameFilters;
use Modules\GameDesign\Domain\Models\DesignRecord;
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
     * What has been decided about a game's design.
     *
     * Read through the game's own relation, so the workspace scoping that
     * produced the game holds here too. The mechanics come with it because a
     * design record without them is unreadable — the terms a game claims are
     * part of the answer, not a detail to fetch later.
     *
     * Null when nothing has been decided, which is most games. That absence is
     * the answer rather than a missing row to paper over.
     */
    public function designRecordOf(Game $game): ?DesignRecord
    {
        $record = $game->designRecord()->with('mechanics')->first();

        return $record?->setRelation('game', $game);
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
     * How many games a workspace holds.
     */
    public function countForWorkspace(Workspace $workspace): int
    {
        return $this->gamesIn($workspace)->count();
    }

    /**
     * How many iterations have been cut across a workspace's games.
     *
     * Counted through the game rather than from a column on the workspace,
     * because the workspace does not own versions and a denormalised total
     * would be a second answer waiting to disagree with this one.
     */
    public function countVersionsInWorkspace(Workspace $workspace): int
    {
        return GameVersion::query()
            ->whereHas('game', fn (Builder $query) => $query->where('workspace_id', $workspace->getKey()))
            ->count();
    }

    /**
     * How a workspace's games are spread across the lifecycle.
     *
     * Sparse on purpose: the database can only report the values it holds rows
     * for, and inventing the missing ones here would mean this method knowing
     * which enum it just grouped by. The caller has that enum and fills the
     * gaps — see {@see GetWorkspaceDesignActivity}.
     *
     * @return array<string, int> keyed by the status value
     */
    public function statusTallyForWorkspace(Workspace $workspace): array
    {
        return $this->asTally(
            $this->gamesIn($workspace)
                ->groupBy('status')
                ->selectRaw('status as value, COUNT(*) as total')
        );
    }

    /**
     * How far along a workspace's games are.
     *
     * Sparse for the same reason as the tally above.
     *
     * @return array<string, int> keyed by the phase value
     */
    public function designPhaseTallyForWorkspace(Workspace $workspace): array
    {
        return $this->asTally(
            $this->gamesIn($workspace)
                ->groupBy('design_phase')
                ->selectRaw('design_phase as value, COUNT(*) as total')
        );
    }

    /**
     * The games somebody in this workspace touched most recently.
     *
     * A separate method rather than a slice of {@see forWorkspace()} because
     * the caller only wants a handful: taking five from a list of every game
     * in a studio would read the whole table to throw most of it away, and
     * this runs on the screen every sign in lands on.
     *
     * @return Collection<int, Game>
     */
    public function recentlyUpdatedInWorkspace(Workspace $workspace, int $limit): Collection
    {
        $games = $this->gamesIn($workspace)
            ->withCount('versions')
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $this->withWorkspace($workspace, $games);
    }

    /**
     * The games belonging to a workspace, as a query still to be refined.
     *
     * Shared by the workspace-wide reads above so they cannot drift onto
     * different ideas of what "in this workspace" means.
     *
     * @return Builder<Game>
     */
    private function gamesIn(Workspace $workspace): Builder
    {
        return Game::query()->where('workspace_id', $workspace->getKey());
    }

    /**
     * Read a grouped count back as a map of value to total.
     *
     * The two tallies above differ only in the column they group by, and that
     * column stays a literal in each of them rather than being passed in here:
     * a method that interpolated a caller's string into `selectRaw` would be
     * the shape of an injection even while every caller passed a constant.
     *
     * @param  Builder<Game>  $query  already grouped, selecting `value` and `total`
     * @return array<string, int>
     */
    private function asTally(Builder $query): array
    {
        /** @var array<string, int> $tally */
        $tally = $query->pluck('total', 'value')->all();

        return array_map('intval', $tally);
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
