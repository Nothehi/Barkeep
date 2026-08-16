<?php

namespace Modules\GameDesign\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Application\DTOs\GameFilters;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The games in a workspace.
 *
 * Always workspace-scoped, and the workspace is a required argument rather
 * than a filter — there is no "all games" query to call by mistake. Filters
 * can only narrow what a caller could already see.
 *
 * Resolution is unauthorized on purpose: finding the games and deciding who
 * may see them are separate steps, and every caller runs the policy against
 * the workspace first. Merging the two would make it easy to forget the
 * second half.
 *
 * @see GameRepository::forWorkspace()
 */
final class GetGames
{
    public function __construct(private readonly GameRepository $games) {}

    /**
     * @return Collection<int, Game>
     */
    public function handle(Workspace $workspace, ?GameFilters $filters = null): Collection
    {
        return $this->games->forWorkspace($workspace, $filters);
    }
}
