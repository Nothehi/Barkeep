<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleCondition;
use Modules\GameRules\Application\Commands\DeleteRuleCondition;
use Modules\GameRules\Application\Commands\UpdateRuleCondition;
use Modules\GameRules\Application\Queries\GetConditions;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleConditionRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleConditionRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleConditionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The named logical requirements a rule system can point at.
 *
 * Declarative and never evaluated. Each carries its statement already worded, so
 * a client renders the sentence rather than assembling it.
 */
class RuleConditionController extends Controller
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
        GetConditions $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return RuleConditionResource::collection($query->handle($ruleSet));
    }

    /**
     * Add one.
     */
    public function store(
        CreateRuleConditionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleCondition $create,
    ): JsonResponse {
        $record = $create->handle($request->user(), $ruleSet, $request->toData());

        return RuleConditionResource::make($record)->response()->setStatusCode(201);
    }

    /**
     * Change one.
     */
    public function update(
        UpdateRuleConditionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleCondition $ruleCondition,
        UpdateRuleCondition $update,
    ): RuleConditionResource {
        return RuleConditionResource::make($update->handle($request->user(), $ruleCondition, $request->toData()));
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
        RuleCondition $ruleCondition,
        DeleteRuleCondition $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $ruleCondition);

        return response()->json(status: 204);
    }
}
