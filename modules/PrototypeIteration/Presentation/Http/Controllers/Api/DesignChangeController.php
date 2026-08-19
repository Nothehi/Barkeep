<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateDesignChange;
use Modules\PrototypeIteration\Application\Commands\DeleteDesignChange;
use Modules\PrototypeIteration\Application\Commands\UpdateDesignChange;
use Modules\PrototypeIteration\Application\Queries\GetDesignChanges;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateDesignChangeRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\DeleteDesignChangeRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateDesignChangeRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignChangeResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The modifications recorded during a design cycle.
 *
 * The only child of an iteration with a full set of verbs, delete included. That is because a
 * change is a statement of fact about an edit rather than a piece of reasoning: while the cycle is
 * open, one entered against the wrong iteration is a bookkeeping error worth removing. Experiments
 * and decisions carry results and arguments, so neither has a delete route at all.
 *
 * Every verb here is gated on the cycle still being open, which is what section 53 amounts to in
 * practice: once an iteration completes, its changes are part of the design history.
 */
class DesignChangeController extends Controller
{
    /**
     * List the cycle's changes, in the order they were recorded.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        GetDesignChanges $getChanges,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $iteration);

        return DesignChangeResource::collection($getChanges->handle($iteration));
    }

    /**
     * Record something the designer deliberately changed.
     */
    public function store(
        CreateDesignChangeRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateDesignChange $createChange,
    ): JsonResponse {
        $change = $createChange->handle($request->user(), $iteration, $request->toData());

        return DesignChangeResource::make($change)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Reword a change.
     */
    public function update(
        UpdateDesignChangeRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignChange $change,
        UpdateDesignChange $updateChange,
    ): DesignChangeResource {
        $updateChange->handle($request->user(), $change, $request->toData());

        return DesignChangeResource::make($change->load('creator'));
    }

    /**
     * Remove a change entered by mistake.
     */
    public function destroy(
        DeleteDesignChangeRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignChange $change,
        DeleteDesignChange $deleteChange,
    ): JsonResponse {
        $deleteChange->handle($request->user(), $change);

        return response()->json(status: 204);
    }
}
