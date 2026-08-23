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
use Modules\GameRules\Application\Commands\CreateRuleAction;
use Modules\GameRules\Application\Commands\DeleteRuleAction;
use Modules\GameRules\Application\Commands\ReorderRuleActions;
use Modules\GameRules\Application\Commands\UpdateRuleAction;
use Modules\GameRules\Application\Queries\GetGamePhases;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\GameEconomy\EconomyDirectory;
use Modules\GameRules\Presentation\Http\Controllers\Web\Concerns\RendersRuleScreens;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleActionRequest;
use Modules\GameRules\Presentation\Http\Requests\ReorderRuleActionsRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleActionRequest;
use Modules\GameRules\Presentation\Http\Resources\EconomyReferenceResource;
use Modules\GameRules\Presentation\Http\Resources\GamePhaseResource;
use Modules\GameRules\Presentation\Http\Resources\RuleActionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The things a player may do.
 */
class RuleActionController extends Controller
{
    use RendersRuleScreens;

    /**
     * Declare something a player may do.
     */
    public function store(
        CreateRuleActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleAction $createAction,
    ): RedirectResponse {
        $createAction->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Action added.')]);

        return back();
    }

    /**
     * One action: when it can be taken, what it needs, and what it does.
     *
     * The economic side is fetched rather than stored. `economyReference` is
     * whatever the version's active balance profile says about the handle this
     * action names, read at render time through the one adapter allowed to reach
     * GameEconomy — so the costs shown here and on the balance screen are the same
     * numbers rather than two copies of them.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleAction $ruleAction,
        GetGamePhases $getPhases,
        EconomyDirectory $economy,
    ): Response {
        Gate::authorize('view', $ruleSet);

        $ruleAction->load(['phase', 'requirements', 'effects']);

        return Inertia::render('rules/action', [
            ...$this->ruleScreenProps($workspace, $game, $version, $ruleSet),
            'action' => RuleActionResource::make($ruleAction),
            'phases' => GamePhaseResource::collection($getPhases->handle($ruleSet)),
            'economy' => $this->economyProps($version),
            'economyReference' => EconomyReferenceResource::make(
                $economy->resolveAction($version, $ruleAction->economy_action_slug),
            ),
        ]);
    }

    /**
     * Rename an action, move it to another phase, or wire it to the economy.
     */
    public function update(
        UpdateRuleActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleAction $ruleAction,
        UpdateRuleAction $updateAction,
    ): RedirectResponse {
        $updateAction->handle($request->user(), $ruleAction, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Action updated.')]);

        return back();
    }

    /**
     * Remove an action, and everything it required and did.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleAction $ruleAction,
        DeleteRuleAction $deleteAction,
    ): RedirectResponse {
        $deleteAction->handle($request->user(), $ruleAction);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Action removed.')]);

        return to_route('rules.show', [$workspace, $game, $version, $ruleSet]);
    }

    /**
     * Put the actions into the order the designer arranged them in.
     */
    public function reorder(
        ReorderRuleActionsRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ReorderRuleActions $reorder,
    ): RedirectResponse {
        $reorder->handle($request->user(), $ruleSet, $request->orderedIds());

        return back();
    }
}
