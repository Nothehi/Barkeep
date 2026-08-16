<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CancelPlaytestSession;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CancelSessionRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSessionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Calling a session off, before it started or part way through.
 *
 * Whatever was recorded before the cancellation stays: four observations from
 * an abandoned session are four things somebody noticed, and the reason it was
 * abandoned is usually among them.
 */
class SessionCancellationController extends Controller
{
    /**
     * Cancel the session.
     */
    public function store(
        CancelSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CancelPlaytestSession $cancelSession,
    ): PlaytestSessionResource {
        $cancelSession->handle($request->user(), $session);

        return PlaytestSessionResource::make(
            $session->load('creator')->loadCount(['participants', 'observations', 'feedback']),
        );
    }
}
