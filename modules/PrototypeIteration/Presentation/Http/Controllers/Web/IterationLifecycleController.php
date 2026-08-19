<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CancelIteration;
use Modules\PrototypeIteration\Application\Commands\CompleteIteration;
use Modules\PrototypeIteration\Application\Commands\StartIteration;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CancelIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\CompleteIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\StartIterationRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving a design cycle through its life, from the screen.
 *
 * The completion message says what completing means rather than that it worked, because the reader
 * is about to find the whole cycle read-only and should know that this was the moment it became
 * history rather than a bug.
 */
class IterationLifecycleController extends Controller
{
    /**
     * Begin the work.
     */
    public function start(
        StartIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        StartIteration $startIteration,
    ): RedirectResponse {
        $startIteration->handle($request->user(), $iteration);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Iteration started.')]);

        return back();
    }

    /**
     * Close the cycle with an outcome and a summary.
     */
    public function complete(
        CompleteIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CompleteIteration $completeIteration,
    ): RedirectResponse {
        $completeIteration->handle($request->user(), $iteration, $request->toData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Iteration completed. It is now part of the design history and read-only.'),
        ]);

        return back();
    }

    /**
     * Call the cycle off.
     */
    public function cancel(
        CancelIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CancelIteration $cancelIteration,
    ): RedirectResponse {
        $cancelIteration->handle($request->user(), $iteration);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Iteration cancelled.')]);

        return back();
    }
}
