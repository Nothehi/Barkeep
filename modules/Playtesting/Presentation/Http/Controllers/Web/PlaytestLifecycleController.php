<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CancelPlaytest;
use Modules\Playtesting\Application\Commands\CompletePlaytest;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Presentation\Http\Requests\CancelPlaytestRequest;
use Modules\Playtesting\Presentation\Http\Requests\CompletePlaytestRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Ending a playtest, one way or the other.
 *
 * Two named actions rather than a status field, because they are decisions
 * with rules — completing requires that something actually happened — and
 * because they mean different things downstream. A route that looked like a
 * field would invite a client to set one.
 */
class PlaytestLifecycleController extends Controller
{
    /**
     * Close the playtest as answered.
     */
    public function complete(
        CompletePlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        CompletePlaytest $completePlaytest,
    ): RedirectResponse {
        $completePlaytest->handle($request->user(), $playtest, $request->conclusion());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Playtest completed.')]);

        return back();
    }

    /**
     * Call the playtest off.
     */
    public function cancel(
        CancelPlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        CancelPlaytest $cancelPlaytest,
    ): RedirectResponse {
        $cancelPlaytest->handle($request->user(), $playtest);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Playtest cancelled.')]);

        return back();
    }
}
