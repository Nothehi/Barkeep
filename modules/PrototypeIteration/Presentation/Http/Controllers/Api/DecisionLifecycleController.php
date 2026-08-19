<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\AcceptDecision;
use Modules\PrototypeIteration\Application\Commands\DeferDecision;
use Modules\PrototypeIteration\Application\Commands\RejectDecision;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\SettleDecisionRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignDecisionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Settling a decision.
 *
 * Three POSTs to named actions, and the reason is stronger here than for any other lifecycle in
 * the module. Accepted and rejected are terminal: expressing them as `PATCH {status: ...}` would
 * make the studio's recorded intention an editable field, and the one thing this record must not
 * be is quietly rewritable.
 *
 * Reversal is deliberately not among the three. A decision that turns out to be wrong is
 * superseded by a new decision in a later cycle, which is how the history reads truthfully — see
 * `DecisionStatus` for the whole argument.
 */
class DecisionLifecycleController extends Controller
{
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
    ): DesignDecisionResource {
        $acceptDecision->handle($request->user(), $decision);

        return $this->rendered($decision);
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
    ): DesignDecisionResource {
        $rejectDecision->handle($request->user(), $decision);

        return $this->rendered($decision);
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
    ): DesignDecisionResource {
        $deferDecision->handle($request->user(), $decision);

        return $this->rendered($decision);
    }

    /**
     * Render the decision as it now stands.
     *
     * The decider relation is reloaded rather than reused, because the settlement just wrote it —
     * a stale relation here would show a freshly accepted decision as agreed by nobody.
     */
    private function rendered(DesignDecision $decision): DesignDecisionResource
    {
        return DesignDecisionResource::make(
            $decision->load(['creator', 'decider'])->loadCount('evidence'),
        );
    }
}
