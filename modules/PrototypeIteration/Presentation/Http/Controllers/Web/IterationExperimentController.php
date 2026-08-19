<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CancelExperiment;
use Modules\PrototypeIteration\Application\Commands\CompleteExperiment;
use Modules\PrototypeIteration\Application\Commands\CreateDesignExperiment;
use Modules\PrototypeIteration\Application\Commands\StartExperiment;
use Modules\PrototypeIteration\Application\Commands\UpdateDesignExperiment;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CompleteExperimentRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateExperimentRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\ExperimentLifecycleRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateExperimentRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Designing and running experiments from the iteration screen.
 *
 * Five actions, and the split between them is the record's integrity. Creating and updating write
 * only the question, the hypothesis, the method and the expectation; completing writes only the
 * result and the conclusion. No screen and no route can do both at once, which is what makes a
 * prediction on this page worth reading.
 */
class IterationExperimentController extends Controller
{
    /**
     * Write down a question the studio intends to answer.
     */
    public function store(
        CreateExperimentRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateDesignExperiment $createExperiment,
    ): RedirectResponse {
        $createExperiment->handle($request->user(), $iteration, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experiment added.')]);

        return back();
    }

    /**
     * Refine an experiment's design before it is answered.
     */
    public function update(
        UpdateExperimentRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignExperiment $experiment,
        UpdateDesignExperiment $updateExperiment,
    ): RedirectResponse {
        $updateExperiment->handle($request->user(), $experiment, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experiment updated.')]);

        return back();
    }

    /**
     * Put the experiment into the field.
     */
    public function start(
        ExperimentLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignExperiment $experiment,
        StartExperiment $startExperiment,
    ): RedirectResponse {
        $startExperiment->handle($request->user(), $experiment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experiment started.')]);

        return back();
    }

    /**
     * Record what it actually produced.
     */
    public function complete(
        CompleteExperimentRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignExperiment $experiment,
        CompleteExperiment $completeExperiment,
    ): RedirectResponse {
        $completeExperiment->handle($request->user(), $experiment, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experiment result recorded.')]);

        return back();
    }

    /**
     * Abandon the question.
     */
    public function cancel(
        ExperimentLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignExperiment $experiment,
        CancelExperiment $cancelExperiment,
    ): RedirectResponse {
        $cancelExperiment->handle($request->user(), $experiment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experiment cancelled.')]);

        return back();
    }
}
