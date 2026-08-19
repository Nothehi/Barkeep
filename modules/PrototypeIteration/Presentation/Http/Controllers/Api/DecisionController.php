<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateDesignDecision;
use Modules\PrototypeIteration\Application\Commands\UpdateDesignDecision;
use Modules\PrototypeIteration\Application\Queries\GetDecisions;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateDecisionRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateDecisionRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignDecisionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The conclusions a design cycle reached.
 *
 * No delete route and no status field. A decision is the sentence somebody will read in a year to
 * find out why the game is the way it is, so it is settled through named actions and never
 * removed — and once settled, even its wording is frozen. A studio that changes its mind records a
 * new decision in a later cycle, which is a truer account than an edited one.
 */
class DecisionController extends Controller
{
    /**
     * List the cycle's decisions, in the order they were proposed.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        GetDecisions $getDecisions,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $iteration);

        return DesignDecisionResource::collection($getDecisions->handle($iteration));
    }

    /**
     * Propose a conclusion.
     */
    public function store(
        CreateDecisionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateDesignDecision $createDecision,
    ): JsonResponse {
        $decision = $createDecision->handle($request->user(), $iteration, $request->toData());

        return DesignDecisionResource::make($decision->loadCount('evidence'))
            ->response()
            ->setStatusCode(201);
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
    ): DesignDecisionResource {
        $updateDecision->handle($request->user(), $decision, $request->toData());

        return DesignDecisionResource::make(
            $decision->load(['creator', 'decider'])->loadCount('evidence'),
        );
    }
}
