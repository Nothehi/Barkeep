<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleRequirement;
use Modules\GameRules\Application\Commands\DeleteRuleRequirement;
use Modules\GameRules\Application\Commands\UpdateRuleRequirement;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleRequirementRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleRequirementRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What has to be true before a rule or an action applies.
 *
 * Every write answers with a redirect rather than JSON, so the reloaded page
 * brings the new record, the recomputed counts and the refreshed findings
 * together. That matters more here than in most modules: the rules dashboard
 * shows the same rule set eight ways at once, and almost every write moves
 * several of them — adding a transition can turn an unreachable phase into a
 * reachable one three sections further down.
 */
class RuleRequirementController extends Controller
{
    /**
     * Gate a rule or an action on something.
     */
    public function store(
        CreateRuleRequirementRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleRequirement $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Requirement added.')]);

        return back();
    }

    /**
     * Reword a requirement, change its threshold, or re-price it.
     */
    public function update(
        UpdateRuleRequirementRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleRequirement $requirement,
        UpdateRuleRequirement $update,
    ): RedirectResponse {
        $update->handle($request->user(), $requirement, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Requirement updated.')]);

        return back();
    }

    /**
     * Remove a gate.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleRequirement $requirement,
        DeleteRuleRequirement $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $requirement);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Requirement removed.')]);

        return back();
    }
}
