<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateGameEndCondition;
use Modules\GameRules\Application\Commands\DeleteGameEndCondition;
use Modules\GameRules\Application\Commands\UpdateGameEndCondition;
use Modules\GameRules\Application\Queries\GetGameEndConditions;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateOutcomeRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateOutcomeRequest;
use Modules\GameRules\Presentation\Http\Resources\GameEndConditionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The things that bring this game to a close.
 *
 * One of three outcome endpoints that stay separate. Winning, losing and the game
 * being over are three different questions a game answers at once, and collapsing
 * them behind one address with a `kind` parameter would make "which of these ends
 * the game" a query string rather than a fact.
 */
class GameEndConditionController extends Controller
{
    /**
     * List them, in the order they are checked.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetGameEndConditions $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return GameEndConditionResource::collection($query->handle($ruleSet));
    }

    /**
     * Record one.
     */
    public function store(
        CreateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateGameEndCondition $create,
    ): JsonResponse {
        $outcome = $create->handle($request->user(), $ruleSet, $request->toData());

        return GameEndConditionResource::make($outcome)->response()->setStatusCode(201);
    }

    /**
     * Reword one, attach a condition to it, or reorder it.
     */
    public function update(
        UpdateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameEndCondition $endCondition,
        UpdateGameEndCondition $update,
    ): GameEndConditionResource {
        return GameEndConditionResource::make($update->handle($request->user(), $endCondition, $request->toData()));
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
        GameEndCondition $endCondition,
        DeleteGameEndCondition $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $endCondition);

        return response()->json(status: 204);
    }
}
