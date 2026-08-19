<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CancelExperiment;
use Modules\PrototypeIteration\Application\Commands\CompleteExperiment;
use Modules\PrototypeIteration\Application\Commands\StartExperiment;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CompleteExperimentRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\ExperimentLifecycleRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignExperimentResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving an experiment through its life.
 *
 * Independent of the cycle around it, which is section 22's rule expressed as three routes that
 * only a person can call. Completing an iteration does not complete its experiments: one still
 * running when the cycle closed stayed unanswered, and marking it complete on the iteration's
 * behalf would put a result into the record that nobody observed.
 */
class ExperimentLifecycleController extends Controller
{
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
    ): DesignExperimentResource {
        $startExperiment->handle($request->user(), $experiment);

        return DesignExperimentResource::make($experiment->load('creator'));
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
    ): DesignExperimentResource {
        $completeExperiment->handle($request->user(), $experiment, $request->toData());

        return DesignExperimentResource::make($experiment->load('creator'));
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
    ): DesignExperimentResource {
        $cancelExperiment->handle($request->user(), $experiment);

        return DesignExperimentResource::make($experiment->load('creator'));
    }
}
