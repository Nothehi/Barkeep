<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CancelPlaytest;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Presentation\Http\Requests\CancelPlaytestRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Calling a playtest off.
 *
 * Separate from completion because they are different outcomes rather than two
 * ways of ending: one produced an answer and the other did not, and anything
 * downstream that treated them alike would credit an investigation that never
 * happened.
 */
class PlaytestCancellationController extends Controller
{
    /**
     * Cancel the playtest.
     */
    public function store(
        CancelPlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        CancelPlaytest $cancelPlaytest,
    ): PlaytestResource {
        $cancelPlaytest->handle($request->user(), $playtest);

        return PlaytestResource::make(
            $playtest->load(['version', 'creator'])->loadCount('sessions'),
        );
    }
}
