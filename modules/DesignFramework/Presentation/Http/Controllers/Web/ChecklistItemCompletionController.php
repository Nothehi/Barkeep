<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\CompleteChecklistItem;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Presentation\Http\Requests\CompletionRequest;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Ticking off one of a game's framework requirements.

 * The framework states the requirement; the game records having met it. The row's existence is the
 * tick, which is what makes a checklist item genuinely binary rather than a workflow.
 *
 * The item was resolved through the framework version this game adopted, so one from another
 * edition 404s before this runs. The write comes back as a redirect, so the reloaded phase page shows
 * what the server actually stored rather than something the client spliced in — which matters on a
 * screen a designer edits repeatedly while thinking.
 */
class ChecklistItemCompletionController extends Controller
{
    /**
     * Record it.
     */
    public function store(
        CompletionRequest $request,
        Workspace $workspace,
        Game $game,
        ChecklistItem $item,
        CompleteChecklistItem $command,
    ): RedirectResponse {
        $command->handle($request->user(), $request->adoption(), $item, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Checklist updated.')]);

        return back();
    }
}
