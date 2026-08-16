<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CreateFeedback;
use Modules\Playtesting\Application\Commands\DeleteFeedback;
use Modules\Playtesting\Application\Commands\UpdateFeedback;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CreateFeedbackRequest;
use Modules\Playtesting\Presentation\Http\Requests\DeleteFeedbackRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdateFeedbackRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Recording what participants say about a session.
 *
 * Behaves like the observation screens for the same reason — it is used while
 * people are still at the table, usually right at the end when everybody is
 * offering an opinion at once.
 */
class PlaytestFeedbackController extends Controller
{
    /**
     * Record what a participant said.
     */
    public function store(
        CreateFeedbackRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CreateFeedback $createFeedback,
    ): RedirectResponse {
        $createFeedback->handle($request->user(), $session, $request->toData());

        return back();
    }

    /**
     * Correct a piece of feedback while the session is still open.
     */
    public function update(
        UpdateFeedbackRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestFeedback $feedback,
        UpdateFeedback $updateFeedback,
    ): RedirectResponse {
        $updateFeedback->handle($request->user(), $session, $feedback, $request->toData());

        return back();
    }

    /**
     * Withdraw a piece of feedback while the session is still open.
     */
    public function destroy(
        DeleteFeedbackRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestFeedback $feedback,
        DeleteFeedback $deleteFeedback,
    ): RedirectResponse {
        $deleteFeedback->handle($request->user(), $session, $feedback);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Feedback removed.')]);

        return back();
    }
}
