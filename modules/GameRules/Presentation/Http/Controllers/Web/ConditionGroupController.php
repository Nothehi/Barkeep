<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\AddConditionToGroup;
use Modules\GameRules\Application\Commands\CreateConditionGroup;
use Modules\GameRules\Application\Commands\DeleteConditionGroup;
use Modules\GameRules\Application\Commands\RemoveConditionFromGroup;
use Modules\GameRules\Application\Commands\UpdateConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\AddConditionToGroupRequest;
use Modules\GameRules\Presentation\Http\Requests\CreateConditionGroupRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateConditionGroupRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Combining conditions under one operator.
 *
 * Membership is managed separately from the group, because the same condition may
 * be in several groups: adding one is a fact about *this* group, and removing one
 * acts on the membership row rather than on the condition — which is why that row
 * has an id of its own.
 */
class ConditionGroupController extends Controller
{
    /**
     * Start a group.
     */
    public function store(
        CreateConditionGroupRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateConditionGroup $createGroup,
    ): RedirectResponse {
        $createGroup->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Condition group added.')]);

        return back();
    }

    /**
     * Rename a group, or switch how its conditions combine.
     */
    public function update(
        UpdateConditionGroupRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ConditionGroup $conditionGroup,
        UpdateConditionGroup $updateGroup,
    ): RedirectResponse {
        $updateGroup->handle($request->user(), $conditionGroup, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Condition group updated.')]);

        return back();
    }

    /**
     * Dissolve a group, leaving its conditions alone.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ConditionGroup $conditionGroup,
        DeleteConditionGroup $deleteGroup,
    ): RedirectResponse {
        $deleteGroup->handle($request->user(), $conditionGroup);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Condition group removed.')]);

        return back();
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
    ): RedirectResponse {
        $addCondition->handle($request->user(), $conditionGroup, $request->conditionId());

        return back();
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
    ): RedirectResponse {
        $removeCondition->handle($request->user(), $conditionGroup, $membership);

        return back();
    }
}
