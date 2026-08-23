<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleTrigger;
use Modules\GameRules\Application\Commands\DeleteRuleTrigger;
use Modules\GameRules\Application\Commands\UpdateRuleTrigger;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleTriggerRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleTriggerRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The things a rule system says happen automatically.
 *
 * Every write answers with a redirect rather than JSON, so the reloaded page
 * brings the new record, the recomputed counts and the refreshed findings
 * together. That matters more here than in most modules: the rules dashboard
 * shows the same rule set eight ways at once, and almost every write moves
 * several of them — adding a transition can turn an unreachable phase into a
 * reachable one three sections further down.
 */
class RuleTriggerController extends Controller
{
    /**
     * Name something that happens automatically.
     */
    public function store(
        CreateRuleTriggerRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleTrigger $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trigger added.')]);

        return back();
    }

    /**
     * Rename a trigger or change when it fires.
     */
    public function update(
        UpdateRuleTriggerRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleTrigger $trigger,
        UpdateRuleTrigger $update,
    ): RedirectResponse {
        $update->handle($request->user(), $trigger, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trigger updated.')]);

        return back();
    }

    /**
     * Remove a trigger.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleTrigger $trigger,
        DeleteRuleTrigger $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $trigger);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trigger removed.')]);

        return back();
    }
}
