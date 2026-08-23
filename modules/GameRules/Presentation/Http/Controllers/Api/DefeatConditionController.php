<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateDefeatCondition;
use Modules\GameRules\Application\Commands\DeleteDefeatCondition;
use Modules\GameRules\Application\Commands\UpdateDefeatCondition;
use Modules\GameRules\Application\Queries\GetDefeatConditions;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateOutcomeRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateOutcomeRequest;
use Modules\GameRules\Presentation\Http\Resources\DefeatConditionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The ways a player can be knocked out.
 *
 * One of three outcome endpoints that stay separate. Winning, losing and the game
 * being over are three different questions a game answers at once, and collapsing
 * them behind one address with a `kind` parameter would make "which of these ends
 * the game" a query string rather than a fact.
 */
class DefeatConditionController extends Controller
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
        GetDefeatConditions $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return DefeatConditionResource::collection($query->handle($ruleSet));
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
        CreateDefeatCondition $create,
    ): JsonResponse {
        $outcome = $create->handle($request->user(), $ruleSet, $request->toData());

        return DefeatConditionResource::make($outcome)->response()->setStatusCode(201);
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
        DefeatCondition $defeatCondition,
        UpdateDefeatCondition $update,
    ): DefeatConditionResource {
        return DefeatConditionResource::make($update->handle($request->user(), $defeatCondition, $request->toData()));
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
        DefeatCondition $defeatCondition,
        DeleteDefeatCondition $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $defeatCondition);

        return response()->json(status: 204);
    }
}
