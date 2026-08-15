<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Application\Commands\ArchiveGame;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Putting a game away.
 *
 * Deliberately not `DELETE /games/{game}`: nothing is destroyed, the game
 * stays readable along with every version of it, and a route that looked like
 * a delete would invite somebody to implement one later.
 */
class GameArchiveController extends Controller
{
    /**
     * Archive the game.
     */
    public function store(Request $request, Workspace $workspace, Game $game, ArchiveGame $archiveGame): GameResource
    {
        Gate::authorize('archive', $game);

        $archiveGame->handle($request->user(), $game);

        return GameResource::make($game->loadCount('versions'));
    }
}
