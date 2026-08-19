<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateDecisionEvidence;
use Modules\PrototypeIteration\Application\Queries\GetDecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateEvidenceRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\DecisionEvidenceResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What a decision cites in support of itself.
 *
 * The list route resolves each citation through the context that owns it, so what comes back is
 * the observation's actual words rather than an id — read live, so a correction to an observation
 * appears in every decision that cited it. Nothing about the cited record is stored here; see
 * `GetDecisionEvidence`.
 *
 * The store route proves the reference belongs to the same game before writing it, which is what
 * makes a reference with no foreign key behind it safe.
 */
class DecisionEvidenceController extends Controller
{
    /**
     * List a decision's citations, resolved.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignDecision $decision,
        GetDecisionEvidence $getEvidence,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $iteration);

        return DecisionEvidenceResource::collection($getEvidence->handle($decision));
    }

    /**
     * Cite something in support of the decision.
     *
     * Answers with the whole resolved list rather than the one row that was written. A citation is
     * only meaningful alongside the others — the panel shows the argument, not the last thing added
     * to it — and resolving the new row on its own would take the same work anyway.
     */
    public function store(
        CreateEvidenceRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignDecision $decision,
        CreateDecisionEvidence $createEvidence,
        GetDecisionEvidence $getEvidence,
    ): JsonResponse {
        $createEvidence->handle($request->user(), $decision, $request->toData());

        return DecisionEvidenceResource::collection($getEvidence->handle($decision))
            ->response()
            ->setStatusCode(201);
    }
}
