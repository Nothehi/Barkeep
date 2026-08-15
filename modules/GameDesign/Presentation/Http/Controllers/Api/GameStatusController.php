<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Application\Commands\ChangeGameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Requests\ChangeGameStatusRequest;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving a game through its project lifecycle.
 *
 * A POST to a named sub-resource rather than a PATCH of the status field:
 * lifecycle moves are actions with rules, not an editable attribute, and a
 * route that looked like a field would invite a client to set one.
 */
class GameStatusController extends Controller
{
    /**
     * Move the game to the requested status.
     */
    public function store(
        ChangeGameStatusRequest $request,
        Workspace $workspace,
        Game $game,
        ChangeGameStatus $changeStatus,
    ): GameResource {
        $changeStatus->handle($request->user(), $game, $request->status());

        return GameResource::make($game->loadCount('versions'));
    }
}
