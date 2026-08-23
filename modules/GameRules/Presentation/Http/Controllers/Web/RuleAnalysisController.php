<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\AnalyseRuleSet;
use Modules\GameRules\Application\Commands\ValidateRuleSet;
use Modules\GameRules\Application\Queries\GetRuleSetAnalysis;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Controllers\Web\Concerns\RendersRuleScreens;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleSetAnalysisResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What the module makes of a rule system.
 *
 * Three endpoints that all read the same numbers and differ only in whether they
 * announce it. The GET is silent, because a page refresh is not a decision and a
 * studio's event stream should not fill up with the fact that somebody had a tab
 * open. The two POSTs dispatch `RuleSetValidated` and `RuleSetAnalysed`, because
 * pressing the button is a fact about how the studio works.
 *
 * None of the three writes anything. A finding is a reading of the rule set as it
 * stands, and storing one would immediately create a second question the module
 * would have to keep answering.
 */
class RuleAnalysisController extends Controller
{
    use RendersRuleScreens;

    /**
     * Show what the validator found, and how much of the rule set there is.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRuleSetAnalysis $getAnalysis,
    ): Response {
        Gate::authorize('view', $ruleSet);

        return Inertia::render('rules/analysis', [
            ...$this->ruleScreenProps($workspace, $game, $version, $ruleSet),
            'analysis' => RuleSetAnalysisResource::make($getAnalysis->handle($ruleSet)),
        ]);
    }

    /**
     * Analyse the rule set, on purpose.
     */
    public function analyse(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        AnalyseRuleSet $analyse,
    ): RedirectResponse {
        $analysis = $analyse->handle($request->user(), $ruleSet);

        Inertia::flash('toast', [
            'type' => $analysis->summary->hasErrors() ? 'error' : 'success',
            'message' => trans_choice(
                '{0}Analysed. Nothing to fix.|{1}Analysed. One problem found.|[2,*]Analysed. :count problems found.',
                count($analysis->findings()),
                ['count' => count($analysis->findings())],
            ),
        ]);

        return back();
    }

    /**
     * Validate the rule set, on purpose.
     */
    public function validateRuleSet(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ValidateRuleSet $validate,
    ): RedirectResponse {
        $findings = $validate->handle($request->user(), $ruleSet);

        Inertia::flash('toast', [
            'type' => $findings === [] ? 'success' : 'warning',
            'message' => trans_choice(
                '{0}Checked. Nothing to fix.|{1}Checked. One problem found.|[2,*]Checked. :count problems found.',
                count($findings),
                ['count' => count($findings)],
            ),
        ]);

        return back();
    }
}
