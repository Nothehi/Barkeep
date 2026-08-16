<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CompletePlaytestSession;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CompleteSessionRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSessionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Ending a session.
 *
 * The last thing done to a session: afterwards it accepts no more
 * participants, observations or feedback, which is what makes everything in it
 * datable.
 */
class SessionCompletionController extends Controller
{
    /**
     * Complete the session, optionally recording what it settled.
     */
    public function store(
        CompleteSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CompletePlaytestSession $completeSession,
    ): PlaytestSessionResource {
        $completeSession->handle($request->user(), $session, $request->toData());

        return PlaytestSessionResource::make(
            $session->load('creator')->loadCount(['participants', 'observations', 'feedback']),
        );
    }
}
