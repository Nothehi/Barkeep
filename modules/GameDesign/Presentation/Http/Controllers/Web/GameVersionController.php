<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Application\Commands\CreateGameVersion;
use Modules\GameDesign\Application\Queries\GetGameVersions;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Presentation\Http\Requests\CreateGameVersionRequest;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The screens for a game's iterations.
 */
class GameVersionController extends Controller
{
    /**
     * Show the game's versions, newest first.
     */
    public function index(Request $request, Workspace $workspace, Game $game, GetGameVersions $getVersions): Response
    {
        Gate::authorize('viewVersions', $game);

        return Inertia::render('games/versions', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'versions' => GameVersionResource::collection($getVersions->handle($game)),
        ]);
    }

    /**
     * Record a new iteration and go to it.
     */
    public function store(
        CreateGameVersionRequest $request,
        Workspace $workspace,
        Game $game,
        CreateGameVersion $createVersion,
    ): RedirectResponse {
        $version = $createVersion->handle($request->user(), $game, $request->toData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':version created.', ['version' => $version->label()]),
        ]);

        return to_route('games.versions.show', [$workspace, $game, $version]);
    }

    /**
     * Show one iteration of the game.
     */
    public function show(Request $request, Workspace $workspace, Game $game, GameVersion $version): Response
    {
        Gate::authorize('viewVersions', $game);

        return Inertia::render('games/version', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'version' => GameVersionResource::make($version->load('creator')),

            /**
             * Stated rather than inferred. A screen could guess by comparing
             * the number against the version count, but that only works while
             * the numbering has no gaps — which is true today and is not a
             * property worth depending on from the client.
             */
            'is_current' => $game->latestVersion?->version_number === $version->version_number,
        ]);
    }
}
