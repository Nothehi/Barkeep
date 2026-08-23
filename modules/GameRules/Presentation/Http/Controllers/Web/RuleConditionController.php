<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleCondition;
use Modules\GameRules\Application\Commands\DeleteRuleCondition;
use Modules\GameRules\Application\Commands\UpdateRuleCondition;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleConditionRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleConditionRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The named logical requirements a rule system can point at.
 *
 * Every write answers with a redirect rather than JSON, so the reloaded page
 * brings the new record, the recomputed counts and the refreshed findings
 * together. That matters more here than in most modules: the rules dashboard
 * shows the same rule set eight ways at once, and almost every write moves
 * several of them — adding a transition can turn an unreachable phase into a
 * reachable one three sections further down.
 */
class RuleConditionController extends Controller
{
    /**
     * Name a reusable logical requirement.
     */
    public function store(
        CreateRuleConditionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleCondition $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Condition added.')]);

        return back();
    }

    /**
     * Change what a condition measures, how, or against what.
     */
    public function update(
        UpdateRuleConditionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleCondition $ruleCondition,
        UpdateRuleCondition $update,
    ): RedirectResponse {
        $update->handle($request->user(), $ruleCondition, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Condition updated.')]);

        return back();
    }

    /**
     * Remove a named condition.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleCondition $ruleCondition,
        DeleteRuleCondition $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $ruleCondition);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Condition removed.')]);

        return back();
    }
}
