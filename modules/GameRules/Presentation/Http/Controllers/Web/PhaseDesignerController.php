<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Queries\GetConditions;
use Modules\GameRules\Application\Queries\GetGamePhases;
use Modules\GameRules\Application\Queries\GetPhaseTransitions;
use Modules\GameRules\Application\Queries\GetRuleActions;
use Modules\GameRules\Application\Queries\GetRuleGraph;
use Modules\GameRules\Application\Queries\GetTriggers;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Controllers\Web\Concerns\RendersRuleScreens;
use Modules\GameRules\Presentation\Http\Resources\GamePhaseResource;
use Modules\GameRules\Presentation\Http\Resources\PhaseTransitionResource;
use Modules\GameRules\Presentation\Http\Resources\RuleActionResource;
use Modules\GameRules\Presentation\Http\Resources\RuleConditionResource;
use Modules\GameRules\Presentation\Http\Resources\RuleGraphResource;
use Modules\GameRules\Presentation\Http\Resources\RuleTriggerResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The visual editor for a game's turn structure.
 *
 * Phases down the left, the flow beside them, and the transitions between them
 * editable in place. The graph travels with it so a designer sees the consequence
 * of an edge as soon as they draw it — the phase that was unreachable a moment ago
 * stops being flagged without a page of its own.
 *
 * No graph library. Section 45 of the brief says to avoid adding one unless the
 * project already has it, and it does not — so the layout is React and Tailwind
 * over nodes the server has already ordered, which is enough for the handful of
 * phases a board game has.
 */
class PhaseDesignerController extends Controller
{
    use RendersRuleScreens;

    /**
     * Show the phase designer.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetGamePhases $getPhases,
        GetPhaseTransitions $getTransitions,
        GetConditions $getConditions,
        GetTriggers $getTriggers,
        GetRuleActions $getActions,
        GetRuleGraph $getGraph,
    ): Response {
        Gate::authorize('view', $ruleSet);

        return Inertia::render('rules/phases', [
            ...$this->ruleScreenProps($workspace, $game, $version, $ruleSet),
            'phases' => GamePhaseResource::collection($getPhases->handle($ruleSet)),
            'transitions' => PhaseTransitionResource::collection($getTransitions->handle($ruleSet)),
            'conditions' => RuleConditionResource::collection($getConditions->handle($ruleSet)),
            'triggers' => RuleTriggerResource::collection($getTriggers->handle($ruleSet)),
            'actions' => RuleActionResource::collection($getActions->handle($ruleSet)),
            'graph' => RuleGraphResource::make($getGraph->handle($ruleSet)),
        ]);
    }
}
