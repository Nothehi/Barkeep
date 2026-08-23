<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Queries\GetGamePhases;
use Modules\GameRules\Application\Queries\GetRuleActions;
use Modules\GameRules\Application\Queries\GetRuleReferences;
use Modules\GameRules\Application\Queries\GetRules;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Controllers\Web\Concerns\RendersRuleScreens;
use Modules\GameRules\Presentation\Http\Resources\GamePhaseResource;
use Modules\GameRules\Presentation\Http\Resources\GameRuleResource;
use Modules\GameRules\Presentation\Http\Resources\RuleActionResource;
use Modules\GameRules\Presentation\Http\Resources\RuleReferenceResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The structured editor for a rule system.
 *
 *     Rule set
 *     ├── Setup
 *     └── Round
 *         ├── Round start
 *         ├── Action phase
 *         │   ├── Build
 *         │   ├── Move
 *         │   └── Trade
 *         ├── Resolution
 *         └── Cleanup
 *
 * Section 44 of the brief, and the constraint that matters in it: this is not an
 * unstructured text editor. A rulebook typed into a textarea is a document nothing
 * can validate, nothing can clone reliably and nothing can turn into a graph —
 * which is the whole reason this module models rules as records rather than as
 * prose.
 *
 * The rules arrive flat and the client assembles the tree from `parent_rule_id`.
 * One query rather than one per level, and a cycle in the data cannot make the
 * rendering recurse forever.
 */
class RuleBuilderController extends Controller
{
    use RendersRuleScreens;

    /**
     * Show the builder.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRules $getRules,
        GetGamePhases $getPhases,
        GetRuleActions $getActions,
        GetRuleReferences $getReferences,
    ): Response {
        Gate::authorize('view', $ruleSet);

        return Inertia::render('rules/builder', [
            ...$this->ruleScreenProps($workspace, $game, $version, $ruleSet),
            'rules' => GameRuleResource::collection($getRules->handle($ruleSet)),
            'phases' => GamePhaseResource::collection($getPhases->handle($ruleSet)),
            'actions' => RuleActionResource::collection($getActions->handle($ruleSet)),
            'references' => RuleReferenceResource::collection($getReferences->handle($ruleSet)),
        ]);
    }
}
