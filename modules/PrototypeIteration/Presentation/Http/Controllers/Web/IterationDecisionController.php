<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\AcceptDecision;
use Modules\PrototypeIteration\Application\Commands\CreateDesignDecision;
use Modules\PrototypeIteration\Application\Commands\DeferDecision;
use Modules\PrototypeIteration\Application\Commands\RejectDecision;
use Modules\PrototypeIteration\Application\Commands\UpdateDesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateDecisionRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\SettleDecisionRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateDecisionRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Proposing and settling decisions from the iteration screen.
 *
 * The three settlement actions are the [Accept] [Reject] [Defer] buttons section 44 asks for, and
 * they are three routes rather than one status field for the reason that runs through this whole
 * module: acceptance is terminal, and the studio's recorded intention must not be an editable
 * value.
 *
 * The acceptance message says what acceptance means, because the reader is about to find the
 * decision frozen — including its wording — and should know that this was the moment rather than a
 * fault.
 */
class IterationDecisionController extends Controller
{
    /**
     * Propose a conclusion.
     */
    public function store(
        CreateDecisionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateDesignDecision $createDecision,
    ): RedirectResponse {
        $createDecision->handle($request->user(), $iteration, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision proposed.')]);

        return back();
    }

    /**
     * Reword a decision that is still open.
     */
    public function update(
        UpdateDecisionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignDecision $decision,
        UpdateDesignDecision $updateDecision,
    ): RedirectResponse {
        $updateDecision->handle($request->user(), $decision, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision updated.')]);

        return back();
    }

    /**
     * Agree the conclusion.
     */
    public function accept(
        SettleDecisionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignDecision $decision,
        AcceptDecision $acceptDecision,
    ): RedirectResponse {
        $acceptDecision->handle($request->user(), $decision);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Decision accepted. Record a new decision if this changes later.'),
        ]);

        return back();
    }

    /**
     * Decide against it.
     */
    public function reject(
        SettleDecisionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignDecision $decision,
        RejectDecision $rejectDecision,
    ): RedirectResponse {
        $rejectDecision->handle($request->user(), $decision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision rejected.')]);

        return back();
    }

    /**
     * Put it off until there is more to go on.
     */
    public function defer(
        SettleDecisionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignDecision $decision,
        DeferDecision $deferDecision,
    ): RedirectResponse {
        $deferDecision->handle($request->user(), $decision);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision deferred.')]);

        return back();
    }
}
