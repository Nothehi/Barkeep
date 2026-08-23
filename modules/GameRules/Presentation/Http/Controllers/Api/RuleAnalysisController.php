<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\AnalyseRuleSet;
use Modules\GameRules\Application\Commands\ValidateRuleSet;
use Modules\GameRules\Application\Queries\GetRuleGraph;
use Modules\GameRules\Application\Queries\GetRuleSetAnalysis;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleGraphResource;
use Modules\GameRules\Presentation\Http\Resources\RuleSetAnalysisResource;
use Modules\GameRules\Presentation\Http\Resources\ValidationErrorResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What the module makes of a rule system.
 *
 * `GET analysis` and `POST analysis` return exactly the same numbers and differ
 * only in whether they announce it: the GET is silent because a page refresh is
 * not a decision, and the POST dispatches `RuleSetAnalysed` because pressing the
 * button is a fact about how a studio works. Neither writes anything.
 *
 * `POST validate` is the narrower of the two — findings without the counts and the
 * collections — for a caller that wants to know whether the rule set holds
 * together and nothing else.
 */
class RuleAnalysisController extends Controller
{
    /**
     * Read the analysis, silently.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRuleSetAnalysis $getAnalysis,
    ): RuleSetAnalysisResource {
        Gate::authorize('view', $ruleSet);

        return RuleSetAnalysisResource::make($getAnalysis->handle($ruleSet));
    }

    /**
     * Analyse the rule set, on purpose.
     */
    public function store(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        AnalyseRuleSet $analyse,
    ): RuleSetAnalysisResource {
        return RuleSetAnalysisResource::make($analyse->handle($request->user(), $ruleSet));
    }

    /**
     * Check the rule set, on purpose, and return only what was found.
     */
    public function validateRuleSet(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ValidateRuleSet $validate,
    ): AnonymousResourceCollection {
        return ValidationErrorResource::collection($validate->handle($request->user(), $ruleSet));
    }

    /**
     * The flow of the game, drawn from its phases and transitions.
     */
    public function graph(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRuleGraph $getGraph,
    ): RuleGraphResource {
        Gate::authorize('view', $ruleSet);

        return RuleGraphResource::make($getGraph->handle($ruleSet));
    }
}
