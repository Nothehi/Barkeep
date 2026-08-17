<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\EvaluateCriterion;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Presentation\Http\Requests\EvaluateCriterionRequest;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Recording how a game measures up against a criterion.

 * The evaluation belongs to the game's adoption of the framework, never to the criterion — see section
 * 22. What that buys concretely: two studios following the same published edition grade the same
 * question independently, and neither can see or affect the other's answer.
 *
 * The criterion was resolved through the framework version this game adopted, so one from another
 * edition 404s before this runs. The write comes back as a redirect, so the reloaded phase page shows
 * what the server actually stored rather than something the client spliced in — which matters on a
 * screen a designer edits repeatedly while thinking.
 */
class CriterionEvaluationController extends Controller
{
    /**
     * Record it.
     */
    public function store(
        EvaluateCriterionRequest $request,
        Workspace $workspace,
        Game $game,
        DesignCriterion $criterion,
        EvaluateCriterion $command,
    ): RedirectResponse {
        $command->handle($request->user(), $request->adoption(), $criterion, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evaluation saved.')]);

        return back();
    }
}
