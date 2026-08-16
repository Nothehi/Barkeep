<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CreatePlaytest;
use Modules\Playtesting\Application\Commands\UpdatePlaytest;
use Modules\Playtesting\Application\Queries\GetPlaytests;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Presentation\Http\Requests\CreatePlaytestRequest;
use Modules\Playtesting\Presentation\Http\Requests\PlaytestFilterRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdatePlaytestRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestResource;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSummaryResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A game's playtests.
 *
 * Nested under the game the same way games are nested under the workspace, and
 * resolved through the same chained bindings, so a playtest id from another
 * project cannot be reached from here.
 *
 * The list returns summaries and the detail route returns everything. A
 * playtests screen renders many rows and needs none of the per-playtest
 * permission and transition answers the full resource computes.
 */
class PlaytestController extends Controller
{
    /**
     * List the game's playtests, most recently planned first.
     */
    public function index(
        PlaytestFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GetPlaytests $getPlaytests,
    ): AnonymousResourceCollection {
        return PlaytestSummaryResource::collection(
            $getPlaytests->handle($game, $request->toFilters()),
        );
    }

    /**
     * Plan a playtest against a version of the game.
     */
    public function store(
        CreatePlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        CreatePlaytest $createPlaytest,
    ): JsonResponse {
        $playtest = $createPlaytest->handle($request->user(), $game, $request->toData());

        return PlaytestResource::make($playtest->loadCount('sessions'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show one playtest in full.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
    ): PlaytestResource {
        Gate::authorize('view', $playtest);

        return PlaytestResource::make(
            $playtest->load(['version', 'creator'])->loadCount('sessions'),
        );
    }

    /**
     * Change a playtest's plan, its conclusion, or both.
     */
    public function update(
        UpdatePlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        UpdatePlaytest $updatePlaytest,
    ): PlaytestResource {
        $updatePlaytest->handle($request->user(), $playtest, $request->toData());

        return PlaytestResource::make(
            $playtest->load(['version', 'creator'])->loadCount('sessions'),
        );
    }
}
