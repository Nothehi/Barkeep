<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\StartPlaytestSession;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\StartSessionRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSessionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Beginning a session.
 *
 * Takes no input: the start time comes from the clock, because it is the
 * anchor the duration, the timeline and the elapsed counter all hang off.
 */
class SessionStartController extends Controller
{
    /**
     * Start the session.
     */
    public function store(
        StartSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        StartPlaytestSession $startSession,
    ): PlaytestSessionResource {
        $startSession->handle($request->user(), $session);

        return PlaytestSessionResource::make(
            $session->load('creator')->loadCount(['participants', 'observations', 'feedback']),
        );
    }
}
