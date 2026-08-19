<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateIteration;
use Modules\PrototypeIteration\Application\Commands\UpdateIteration;
use Modules\PrototypeIteration\Application\Queries\GetIterations;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\IterationFilterRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationCardResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A game's design cycles.
 *
 * Nested under the game like everything else in this module, so an iteration id from another
 * project cannot be reached from here — it fails to resolve before a handler runs.
 *
 * The list returns cards and the detail route returns everything. That split matters more here
 * than anywhere else in the platform: the full resource asks the gate eight times per iteration,
 * and a project two years in has dozens of cycles.
 */
class IterationController extends Controller
{
    /**
     * List the game's design cycles, newest first.
     */
    public function index(
        IterationFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GetIterations $getIterations,
    ): AnonymousResourceCollection {
        return IterationCardResource::collection(
            $getIterations->handle($game, $request->toFilters()),
        );
    }

    /**
     * Plan a cycle against a design version and a prototype state.
     */
    public function store(
        CreateIterationRequest $request,
        Workspace $workspace,
        Game $game,
        CreateIteration $createIteration,
    ): JsonResponse {
        $iteration = $createIteration->handle($request->user(), $game, $request->toData());

        return IterationResource::make(
            $iteration->loadCount(['changes', 'experiments', 'decisions', 'playtestLinks']),
        )->response()->setStatusCode(201);
    }

    /**
     * Show one cycle in full.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
    ): IterationResource {
        Gate::authorize('view', $iteration);

        return IterationResource::make($this->rendered($iteration));
    }

    /**
     * Change a cycle's plan.
     */
    public function update(
        UpdateIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        UpdateIteration $updateIteration,
    ): IterationResource {
        $updateIteration->handle($request->user(), $iteration, $request->toData());

        return IterationResource::make($this->rendered($iteration));
    }

    /**
     * Load what the full representation of a cycle needs.
     *
     * Gathered here because four routes on this controller and its lifecycle sibling all render
     * the same shape, and a list of eager loads repeated five times is a list that ends up
     * different in one of them.
     */
    private function rendered(Iteration $iteration): Iteration
    {
        return $iteration
            ->load(['version', 'prototypeVersion.prototype', 'creator'])
            ->loadCount(['changes', 'experiments', 'decisions', 'playtestLinks']);
    }
}
