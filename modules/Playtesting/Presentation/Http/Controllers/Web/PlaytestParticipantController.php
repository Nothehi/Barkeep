<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\AddParticipant;
use Modules\Playtesting\Application\Commands\RemoveParticipant;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\AddParticipantRequest;
use Modules\Playtesting\Presentation\Http\Requests\RemoveParticipantRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Adding and removing the people at a session.
 *
 * Both redirect back to the session, so somebody adding four players in a row
 * stays on the screen they are working from.
 */
class PlaytestParticipantController extends Controller
{
    /**
     * Seat somebody at the session.
     */
    public function store(
        AddParticipantRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        AddParticipant $addParticipant,
    ): RedirectResponse {
        $participant = $addParticipant->handle($request->user(), $session, $request->toData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name added.', ['name' => $participant->display_name]),
        ]);

        return back();
    }

    /**
     * Take somebody off the session.
     */
    public function destroy(
        RemoveParticipantRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestParticipant $participant,
        RemoveParticipant $removeParticipant,
    ): RedirectResponse {
        $name = $participant->display_name;

        $removeParticipant->handle($request->user(), $session, $participant);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name removed.', ['name' => $name]),
        ]);

        return back();
    }
}
