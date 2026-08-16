<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CreateObservation;
use Modules\Playtesting\Application\Commands\DeleteObservation;
use Modules\Playtesting\Application\Commands\UpdateObservation;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CreateObservationRequest;
use Modules\Playtesting\Presentation\Http\Requests\DeleteObservationRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdateObservationRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Recording what a designer notices during a session.
 *
 * Every action here redirects back with the scroll position kept, because this
 * is used repeatedly during a live session and losing your place in a growing
 * timeline after every note would make the screen unusable.
 *
 * No toast on creation either: the observation appearing in the timeline is
 * the confirmation, and a message per note would bury the screen in them.
 */
class PlaytestObservationController extends Controller
{
    /**
     * Record something noticed.
     */
    public function store(
        CreateObservationRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CreateObservation $createObservation,
    ): RedirectResponse {
        $createObservation->handle($request->user(), $session, $request->toData());

        return back();
    }

    /**
     * Correct an observation while the session is still open.
     */
    public function update(
        UpdateObservationRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestObservation $observation,
        UpdateObservation $updateObservation,
    ): RedirectResponse {
        $updateObservation->handle($request->user(), $session, $observation, $request->toData());

        return back();
    }

    /**
     * Withdraw an observation while the session is still open.
     */
    public function destroy(
        DeleteObservationRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestObservation $observation,
        DeleteObservation $deleteObservation,
    ): RedirectResponse {
        $deleteObservation->handle($request->user(), $session, $observation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Observation removed.')]);

        return back();
    }
}
