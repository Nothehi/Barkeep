<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleSet;
use Modules\GameRules\Application\Commands\UpdateRuleSet;
use Modules\GameRules\Application\Queries\GetActiveRuleSet;
use Modules\GameRules\Application\Queries\GetRuleSetAnalysis;
use Modules\GameRules\Application\Queries\GetRuleSets;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Controllers\Web\Concerns\RendersRuleScreens;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleSetRequest;
use Modules\GameRules\Presentation\Http\Requests\RuleSetFilterRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleSetRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleSetAnalysisResource;
use Modules\GameRules\Presentation\Http\Resources\RuleSetResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The rule sets of a design state, and the dashboard for one of them.
 */
class RuleSetController extends Controller
{
    use RendersRuleScreens;

    /**
     * List the rule systems written for this design state.
     */
    public function index(
        RuleSetFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        GetRuleSets $getRuleSets,
        GetActiveRuleSet $getActive,
    ): Response {
        $filters = $request->toFilters();
        $active = $getActive->handle($version);

        return Inertia::render('rules/index', [
            ...$this->ruleScreenProps($workspace, $game, $version),
            'ruleSets' => RuleSetResource::collection($getRuleSets->handle($version, $filters)),
            /*
             * Null rather than an empty resource. A version whose rules nobody
             * has activated has no rule set in play, which is most of them for
             * most of their life — and `JsonResource::make(null)` serialises by
             * reading properties off nothing.
             */
            'activeRuleSet' => $active === null ? null : RuleSetResource::make($active),
            'filters' => $filters->toArray(),
            'canCreate' => $this->canCreateRuleSet($request, $version),
        ]);
    }

    /**
     * Start writing a game's rules down.
     */
    public function store(
        CreateRuleSetRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        CreateRuleSet $createRuleSet,
    ): RedirectResponse {
        $ruleSet = $createRuleSet->handle($request->user(), $version, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule set created.')]);

        return to_route('rules.show', [$workspace, $game, $version, $ruleSet]);
    }

    /**
     * The whole rule system of one design state, on one screen.
     *
     * Every section reads from the same analysis, which is a deliberate departure
     * from how some other screens work: the findings are *about* the rules, the
     * phases and the actions, and a page that fetched them separately would spend
     * part of its life showing errors about a rule set it had not finished
     * receiving.
     */
    public function show(
        RuleSetFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRuleSetAnalysis $getAnalysis,
    ): Response {
        return Inertia::render('rules/show', [
            ...$this->ruleScreenProps($workspace, $game, $version, $ruleSet),
            'analysis' => RuleSetAnalysisResource::make($getAnalysis->handle($ruleSet)),
            'economy' => $this->economyProps($version),
        ]);
    }

    /**
     * Correct the rule set's own title or summary.
     */
    public function update(
        UpdateRuleSetRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        UpdateRuleSet $updateRuleSet,
    ): RedirectResponse {
        $updateRuleSet->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule set updated.')]);

        return back();
    }
}
