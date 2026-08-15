<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Application\Commands\CreateGameVersion;
use Modules\GameDesign\Application\Queries\GetGameVersions;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Presentation\Http\Requests\CreateGameVersionRequest;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A game's iterations.
 *
 * Versions are nested under the game the same way games are nested under the
 * workspace, and resolved through the same scoped bindings, so a version
 * number from another game cannot be reached from here.
 */
class GameVersionController extends Controller
{
    /**
     * List the game's versions, newest first.
     */
    public function index(Request $request, Workspace $workspace, Game $game, GetGameVersions $getVersions): AnonymousResourceCollection
    {
        Gate::authorize('viewVersions', $game);

        return GameVersionResource::collection($getVersions->handle($game));
    }

    /**
     * Record a new iteration of the game.
     *
     * The response carries the number that was allocated, which is the only
     * way the caller finds out what it is.
     */
    public function store(
        CreateGameVersionRequest $request,
        Workspace $workspace,
        Game $game,
        CreateGameVersion $createVersion,
    ): JsonResponse {
        $version = $createVersion->handle($request->user(), $game, $request->toData());

        return GameVersionResource::make($version->load('creator'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show one iteration of the game.
     */
    public function show(Request $request, Workspace $workspace, Game $game, GameVersion $version): GameVersionResource
    {
        Gate::authorize('viewVersions', $game);

        return GameVersionResource::make($version->load('creator'));
    }
}
