<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\AddConditionToGroup;
use Modules\GameRules\Application\Commands\CreateConditionGroup;
use Modules\GameRules\Application\Commands\DeleteConditionGroup;
use Modules\GameRules\Application\Commands\RemoveConditionFromGroup;
use Modules\GameRules\Application\Commands\UpdateConditionGroup;
use Modules\GameRules\Application\Queries\GetConditionGroups;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\AddConditionToGroupRequest;
use Modules\GameRules\Presentation\Http\Requests\CreateConditionGroupRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateConditionGroupRequest;
use Modules\GameRules\Presentation\Http\Resources\ConditionGroupResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Groupings of conditions.
 *
 * Membership has endpoints of its own because a condition may be in several
 * groups: removing it from one acts on the membership row rather than on the
 * condition, which is why that row is addressable at all.
 */
class ConditionGroupController extends Controller
{
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetConditionGroups $getGroups,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return ConditionGroupResource::collection($getGroups->handle($ruleSet));
    }

    public function store(
        CreateConditionGroupRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateConditionGroup $createGroup,
    ): JsonResponse {
        $group = $createGroup->handle($request->user(), $ruleSet, $request->toData());

        return ConditionGroupResource::make($group)->response()->setStatusCode(201);
    }

    public function update(
        UpdateConditionGroupRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ConditionGroup $conditionGroup,
        UpdateConditionGroup $updateGroup,
    ): ConditionGroupResource {
        return ConditionGroupResource::make(
            $updateGroup->handle($request->user(), $conditionGroup, $request->toData()),
        );
    }

    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ConditionGroup $conditionGroup,
        DeleteConditionGroup $deleteGroup,
    ): JsonResponse {
        $deleteGroup->handle($request->user(), $conditionGroup);

        return response()->json(status: 204);
    }

    /**
     * Put a condition into the group.
     */
    public function storeCondition(
        AddConditionToGroupRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ConditionGroup $conditionGroup,
        AddConditionToGroup $addCondition,
    ): ConditionGroupResource {
        return ConditionGroupResource::make(
            $addCondition->handle($request->user(), $conditionGroup, $request->conditionId()),
        );
    }

    /**
     * Take a condition out of the group.
     */
    public function destroyCondition(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ConditionGroup $conditionGroup,
        ConditionGroupCondition $membership,
        RemoveConditionFromGroup $removeCondition,
    ): JsonResponse {
        $removeCondition->handle($request->user(), $conditionGroup, $membership);

        return response()->json(status: 204);
    }
}
