<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\CompletePractice;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Presentation\Http\Requests\CompletionRequest;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Recording that a game has carried out one of its framework's activities.

 * A toggle. Unticking deletes the completion rather than storing a false flag, which is what keeps a
 * completion record a record of a completion.
 *
 * The practice was resolved through the framework version this game adopted, so one from another
 * edition 404s before this runs. The write comes back as a redirect, so the reloaded phase page shows
 * what the server actually stored rather than something the client spliced in — which matters on a
 * screen a designer edits repeatedly while thinking.
 */
class PracticeCompletionController extends Controller
{
    /**
     * Record it.
     */
    public function store(
        CompletionRequest $request,
        Workspace $workspace,
        Game $game,
        DesignPractice $practice,
        CompletePractice $command,
    ): RedirectResponse {
        $command->handle($request->user(), $request->adoption(), $practice, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Practice updated.')]);

        return back();
    }
}
