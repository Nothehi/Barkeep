<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleRequirement;
use Modules\GameRules\Application\Commands\DeleteRuleRequirement;
use Modules\GameRules\Application\Commands\UpdateRuleRequirement;
use Modules\GameRules\Application\Queries\GetRequirements;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleRequirementRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleRequirementRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleRequirementResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What has to be true before a rule or an action applies.
 */
class RuleRequirementController extends Controller
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
        GetRequirements $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return RuleRequirementResource::collection($query->handle($ruleSet));
    }

    /**
     * Add one.
     */
    public function store(
        CreateRuleRequirementRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleRequirement $create,
    ): JsonResponse {
        $record = $create->handle($request->user(), $ruleSet, $request->toData());

        return RuleRequirementResource::make($record)->response()->setStatusCode(201);
    }

    /**
     * Change one.
     */
    public function update(
        UpdateRuleRequirementRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleRequirement $requirement,
        UpdateRuleRequirement $update,
    ): RuleRequirementResource {
        return RuleRequirementResource::make($update->handle($request->user(), $requirement, $request->toData()));
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
        RuleRequirement $requirement,
        DeleteRuleRequirement $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $requirement);

        return response()->json(status: 204);
    }
}
