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
use Modules\GameRules\Application\Commands\CreateGameRule;
use Modules\GameRules\Application\Commands\DeleteGameRule;
use Modules\GameRules\Application\Commands\ReorderGameRules;
use Modules\GameRules\Application\Commands\UpdateGameRule;
use Modules\GameRules\Application\Queries\GetGamePhases;
use Modules\GameRules\Application\Queries\GetRules;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Controllers\Web\Concerns\RendersRuleScreens;
use Modules\GameRules\Presentation\Http\Requests\CreateGameRuleRequest;
use Modules\GameRules\Presentation\Http\Requests\ReorderGameRulesRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateGameRuleRequest;
use Modules\GameRules\Presentation\Http\Resources\GamePhaseResource;
use Modules\GameRules\Presentation\Http\Resources\GameRuleResource;
use Modules\GameRules\Presentation\Http\Resources\RuleReferenceResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The rules themselves.
 */
class GameRuleController extends Controller
{
    use RendersRuleScreens;

    /**
     * Write a rule down.
     */
    public function store(
        CreateGameRuleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateGameRule $createRule,
    ): RedirectResponse {
        $createRule->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule added.')]);

        return back();
    }

    /**
     * One rule, with everything that hangs off it.
     *
     * The rules a rule *references* and the rules that reference *it* both travel
     * with it, because "what breaks if I change this?" is the question somebody
     * asks before editing one, and it is answered by the second of those.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        GetRules $getRules,
        GetGamePhases $getPhases,
    ): Response {
        Gate::authorize('view', $ruleSet);

        $gameRule->load([
            'phase',
            'children',
            'requirements',
            'effects',
            'references.referencedRule',
            'referencedBy.rule',
        ]);

        return Inertia::render('rules/rule', [
            ...$this->ruleScreenProps($workspace, $game, $version, $ruleSet),
            'rule' => GameRuleResource::make($gameRule),
            'referencedBy' => RuleReferenceResource::collection(
                $gameRule->referencedBy,
            ),
            'rules' => GameRuleResource::collection($getRules->handle($ruleSet)),
            'phases' => GamePhaseResource::collection($getPhases->handle($ruleSet)),
            'economy' => $this->economyProps($version),
        ]);
    }

    /**
     * Reword a rule, retype it, move it in the tree, or retire it.
     */
    public function update(
        UpdateGameRuleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        UpdateGameRule $updateRule,
    ): RedirectResponse {
        $updateRule->handle($request->user(), $gameRule, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule updated.')]);

        return back();
    }

    /**
     * Remove a rule, promoting whatever sat under it.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        DeleteGameRule $deleteRule,
    ): RedirectResponse {
        $deleteRule->handle($request->user(), $gameRule);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule removed.')]);

        return to_route('rules.builder', [$workspace, $game, $version, $ruleSet]);
    }

    /**
     * Put the rules into the order the designer dragged them into.
     */
    public function reorder(
        ReorderGameRulesRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ReorderGameRules $reorder,
    ): RedirectResponse {
        $reorder->handle($request->user(), $ruleSet, $request->orderedIds());

        return back();
    }
}
