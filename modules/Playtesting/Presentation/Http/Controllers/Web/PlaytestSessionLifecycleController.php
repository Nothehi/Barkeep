<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CancelPlaytestSession;
use Modules\Playtesting\Application\Commands\CompletePlaytestSession;
use Modules\Playtesting\Application\Commands\StartPlaytestSession;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CancelSessionRequest;
use Modules\Playtesting\Presentation\Http\Requests\CompleteSessionRequest;
use Modules\Playtesting\Presentation\Http\Requests\StartSessionRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Starting, ending and calling off a session.
 *
 * Every one of these redirects back to the session rather than anywhere else,
 * because the person pressing them is standing at a table looking at that
 * screen and expects it to still be there afterwards.
 */
class PlaytestSessionLifecycleController extends Controller
{
    /**
     * Begin the session.
     */
    public function start(
        StartSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        StartPlaytestSession $startSession,
    ): RedirectResponse {
        $startSession->handle($request->user(), $session);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session started.')]);

        return back();
    }

    /**
     * End the session.
     */
    public function complete(
        CompleteSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CompletePlaytestSession $completeSession,
    ): RedirectResponse {
        $completeSession->handle($request->user(), $session, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session ended.')]);

        return back();
    }

    /**
     * Call the session off.
     */
    public function cancel(
        CancelSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CancelPlaytestSession $cancelSession,
    ): RedirectResponse {
        $cancelSession->handle($request->user(), $session);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session cancelled.')]);

        return back();
    }
}
