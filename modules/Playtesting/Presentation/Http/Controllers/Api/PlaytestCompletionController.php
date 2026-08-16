<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CompletePlaytest;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Presentation\Http\Requests\CompletePlaytestRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Closing a playtest as answered.
 *
 * A POST to a named sub-resource rather than a PATCH of the status field:
 * completing is an action with rules — chiefly that something actually
 * happened — and a route that looked like a field would invite a client to
 * set one.
 */
class PlaytestCompletionController extends Controller
{
    /**
     * Complete the playtest, optionally recording what it concluded.
     */
    public function store(
        CompletePlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        CompletePlaytest $completePlaytest,
    ): PlaytestResource {
        $completePlaytest->handle($request->user(), $playtest, $request->conclusion());

        return PlaytestResource::make(
            $playtest->load(['version', 'creator'])->loadCount('sessions'),
        );
    }
}
