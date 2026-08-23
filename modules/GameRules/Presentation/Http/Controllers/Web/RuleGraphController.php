<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Queries\GetGameEndConditions;
use Modules\GameRules\Application\Queries\GetRuleGraph;
use Modules\GameRules\Application\Queries\GetVictoryConditions;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Controllers\Web\Concerns\RendersRuleScreens;
use Modules\GameRules\Presentation\Http\Resources\GameEndConditionResource;
use Modules\GameRules\Presentation\Http\Resources\RuleGraphResource;
use Modules\GameRules\Presentation\Http\Resources\VictoryConditionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The flow of a game, drawn.
 *
 * Read-only, and deliberately so — section 51 of the brief gives editing to the
 * phase designer and leaves this as the picture. Splitting them is what keeps the
 * diagram legible: a canvas that had to support dragging, connecting and deleting
 * would spend its space on affordances rather than on the shape of the game.
 *
 * The outcomes travel with it because a flow that stops at "Cleanup" does not show
 * how the game finishes, and "what ends this?" is the question somebody opens this
 * screen to answer.
 */
class RuleGraphController extends Controller
{
    use RendersRuleScreens;

    /**
     * Show the graph.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRuleGraph $getGraph,
        GetVictoryConditions $getVictory,
        GetGameEndConditions $getEnd,
    ): Response {
        Gate::authorize('view', $ruleSet);

        return Inertia::render('rules/graph', [
            ...$this->ruleScreenProps($workspace, $game, $version, $ruleSet),
            'graph' => RuleGraphResource::make($getGraph->handle($ruleSet)),
            'victoryConditions' => VictoryConditionResource::collection($getVictory->handle($ruleSet)),
            'endConditions' => GameEndConditionResource::collection($getEnd->handle($ruleSet)),
        ]);
    }
}
