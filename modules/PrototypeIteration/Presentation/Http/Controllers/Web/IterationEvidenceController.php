<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateDecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateEvidenceRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Citing evidence for a decision, from the iteration screen.
 *
 * The citation is proved against the decision's own game before it is written, so a picker cannot
 * be talked into citing another studio's observation — which matters more here than on most forms,
 * because the reference has no foreign key behind it and would render as genuine supporting
 * evidence if it got through.
 */
class IterationEvidenceController extends Controller
{
    public function store(
        CreateEvidenceRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignDecision $decision,
        CreateDecisionEvidence $createEvidence,
    ): RedirectResponse {
        $createEvidence->handle($request->user(), $decision, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evidence added.')]);

        return back();
    }
}
