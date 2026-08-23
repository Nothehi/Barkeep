<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleEffect;
use Modules\GameRules\Application\Commands\DeleteRuleEffect;
use Modules\GameRules\Application\Commands\UpdateRuleEffect;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleEffectRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleEffectRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What happens when a rule or an action resolves.
 *
 * Every write answers with a redirect rather than JSON, so the reloaded page
 * brings the new record, the recomputed counts and the refreshed findings
 * together. That matters more here than in most modules: the rules dashboard
 * shows the same rule set eight ways at once, and almost every write moves
 * several of them — adding a transition can turn an unreachable phase into a
 * reachable one three sections further down.
 */
class RuleEffectController extends Controller
{
    /**
     * Record what a rule or an action does.
     */
    public function store(
        CreateRuleEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleEffect $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Effect added.')]);

        return back();
    }

    /**
     * Change what an effect does, to what, or by how much.
     */
    public function update(
        UpdateRuleEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleEffect $ruleEffect,
        UpdateRuleEffect $update,
    ): RedirectResponse {
        $update->handle($request->user(), $ruleEffect, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Effect updated.')]);

        return back();
    }

    /**
     * Remove an effect.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleEffect $ruleEffect,
        DeleteRuleEffect $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $ruleEffect);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Effect removed.')]);

        return back();
    }
}
