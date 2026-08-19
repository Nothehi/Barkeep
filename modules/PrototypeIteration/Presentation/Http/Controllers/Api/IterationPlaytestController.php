<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\AttachPlaytestToIteration;
use Modules\PrototypeIteration\Application\Commands\DetachPlaytestFromIteration;
use Modules\PrototypeIteration\Application\Queries\GetIterationPlaytests;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Presentation\Http\Requests\AttachPlaytestRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\DetachPlaytestRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\PlaytestReferenceResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The playtests a design cycle was tested through.
 *
 * Notice what is absent from every signature here: a `Playtest`. Attaching takes an id in the body
 * and detaching addresses the link, so no route on this controller binds a model belonging to
 * Playtesting — which is what keeps the seam to a single adapter file rather than spreading it
 * across the HTTP layer.
 *
 * What comes back are references, not playtests: the handful of facts an iteration screen needs to
 * recognise the evidence, read from Playtesting at the moment of the request.
 */
class IterationPlaytestController extends Controller
{
    /**
     * List the playtests attached to this cycle.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        GetIterationPlaytests $getPlaytests,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $iteration);

        return PlaytestReferenceResource::collection($getPlaytests->handle($iteration));
    }

    /**
     * Attach a playtest to this cycle.
     */
    public function store(
        AttachPlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        AttachPlaytestToIteration $attachPlaytest,
        GetIterationPlaytests $getPlaytests,
    ): JsonResponse {
        $attachPlaytest->handle($request->user(), $iteration, $request->playtestId());

        return PlaytestReferenceResource::collection($getPlaytests->handle($iteration))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a playtest that did not test this cycle after all.
     */
    public function destroy(
        DetachPlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        IterationPlaytest $link,
        DetachPlaytestFromIteration $detachPlaytest,
    ): JsonResponse {
        $detachPlaytest->handle($request->user(), $link);

        return response()->json(status: 204);
    }
}
