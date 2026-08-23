<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreatePhaseTransition;
use Modules\GameRules\Application\Commands\DeletePhaseTransition;
use Modules\GameRules\Application\Commands\UpdatePhaseTransition;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreatePhaseTransitionRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdatePhaseTransitionRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * How play moves between the phases of a game.
 *
 * Every write answers with a redirect rather than JSON, so the reloaded page
 * brings the new record, the recomputed counts and the refreshed findings
 * together. That matters more here than in most modules: the rules dashboard
 * shows the same rule set eight ways at once, and almost every write moves
 * several of them — adding a transition can turn an unreachable phase into a
 * reachable one three sections further down.
 */
class PhaseTransitionController extends Controller
{
    /**
     * Say how play moves from one phase to the next.
     */
    public function store(
        CreatePhaseTransitionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreatePhaseTransition $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transition added.')]);

        return back();
    }

    /**
     * Change where a transition leads, what guards it, or when it is considered.
     */
    public function update(
        UpdatePhaseTransitionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        PhaseTransition $transition,
        UpdatePhaseTransition $update,
    ): RedirectResponse {
        $update->handle($request->user(), $transition, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transition updated.')]);

        return back();
    }

    /**
     * Remove a way for play to advance.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        PhaseTransition $transition,
        DeletePhaseTransition $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $transition);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transition removed.')]);

        return back();
    }
}
