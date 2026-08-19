<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateDesignExperiment;
use Modules\PrototypeIteration\Application\Commands\UpdateDesignExperiment;
use Modules\PrototypeIteration\Application\Queries\GetExperiments;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateExperimentRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateExperimentRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignExperimentResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The focused questions a design cycle set out to answer.
 *
 * Neither route here can write a result. That is the arrangement the whole experiment record
 * depends on: the prediction is written through these, before the experiment runs, and the result
 * is written through the completion route afterwards — so a single request can never produce a
 * prediction and its own confirmation.
 *
 * There is no delete route. An experiment carries a result once it has run, and removing it would
 * take a finding out of the record; a question that stopped mattering is cancelled, which says so
 * honestly and leaves the record intact.
 */
class ExperimentController extends Controller
{
    /**
     * List the cycle's experiments, in the order they were designed.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        GetExperiments $getExperiments,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $iteration);

        return DesignExperimentResource::collection($getExperiments->handle($iteration));
    }

    /**
     * Write down a question the studio intends to answer.
     */
    public function store(
        CreateExperimentRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateDesignExperiment $createExperiment,
    ): JsonResponse {
        $experiment = $createExperiment->handle($request->user(), $iteration, $request->toData());

        return DesignExperimentResource::make($experiment)
            ->response()
            ->setStatusCode(201);
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
    ): DesignExperimentResource {
        $updateExperiment->handle($request->user(), $experiment, $request->toData());

        return DesignExperimentResource::make($experiment->load('creator'));
    }
}
