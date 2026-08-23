<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateGamePhase;
use Modules\GameRules\Application\Commands\DeleteGamePhase;
use Modules\GameRules\Application\Commands\UpdateGamePhase;
use Modules\GameRules\Application\Queries\GetGamePhases;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateGamePhaseRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateGamePhaseRequest;
use Modules\GameRules\Presentation\Http\Resources\GamePhaseResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The stages of play, in the order play visits them.
 *
 * Phases of the *game*, not of the designer's work — DesignFramework owns the
 * other kind and the two modules do not know about each other.
 */
class GamePhaseController extends Controller
{
    /**
     * List them.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetGamePhases $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return GamePhaseResource::collection($query->handle($ruleSet));
    }

    /**
     * Add one.
     */
    public function store(
        CreateGamePhaseRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateGamePhase $create,
    ): JsonResponse {
        $record = $create->handle($request->user(), $ruleSet, $request->toData());

        return GamePhaseResource::make($record)->response()->setStatusCode(201);
    }

    /**
     * Change one.
     */
    public function update(
        UpdateGamePhaseRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GamePhase $gamePhase,
        UpdateGamePhase $update,
    ): GamePhaseResource {
        return GamePhaseResource::make($update->handle($request->user(), $gamePhase, $request->toData()));
    }

    /**
     * Remove one.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GamePhase $gamePhase,
        DeleteGamePhase $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $gamePhase);

        return response()->json(status: 204);
    }
}
