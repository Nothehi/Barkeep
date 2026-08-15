<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Application\Commands\CreateGame;
use Modules\GameDesign\Application\Commands\UpdateGame;
use Modules\GameDesign\Application\Queries\GetGames;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Requests\CreateGameRequest;
use Modules\GameDesign\Presentation\Http\Requests\GameFilterRequest;
use Modules\GameDesign\Presentation\Http\Requests\UpdateGameRequest;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameSummaryResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The games inside a workspace.
 *
 * Every route here is nested under a workspace and every action authorizes
 * against the resolved binding, so the listing is scoped rather than filtered
 * and there is no request that can reach a game in another workspace.
 */
class GameController extends Controller
{
    /**
     * List the games in the workspace.
     */
    public function index(GameFilterRequest $request, Workspace $workspace, GetGames $getGames): AnonymousResourceCollection
    {
        return GameSummaryResource::collection(
            $getGames->handle($workspace, $request->toFilters()),
        );
    }

    /**
     * Start a new game in the workspace.
     */
    public function store(CreateGameRequest $request, Workspace $workspace, CreateGame $createGame): JsonResponse
    {
        $game = $createGame->handle($request->user(), $workspace, $request->toData());

        return GameResource::make($game->loadCount('versions'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single game.
     */
    public function show(Request $request, Workspace $workspace, Game $game): GameResource
    {
        Gate::authorize('view', $game);

        return GameResource::make($game->loadCount('versions'));
    }

    /**
     * Change a game's name, address or description.
     */
    public function update(UpdateGameRequest $request, Workspace $workspace, Game $game, UpdateGame $updateGame): GameResource
    {
        $updateGame->handle($request->user(), $game, $request->toData());

        return GameResource::make($game->loadCount('versions'));
    }
}
