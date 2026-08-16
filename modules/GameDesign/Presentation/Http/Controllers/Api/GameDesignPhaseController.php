<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Application\Commands\ChangeDesignPhase;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Requests\ChangeDesignPhaseRequest;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Recording where a game has got to in the design process.
 *
 * Separate from the status endpoint because the two concepts are separate: a
 * game can be on hold in the middle of playtesting, and neither value implies
 * anything about the other.
 */
class GameDesignPhaseController extends Controller
{
    /**
     * Record the game's design phase.
     */
    public function store(
        ChangeDesignPhaseRequest $request,
        Workspace $workspace,
        Game $game,
        ChangeDesignPhase $changeDesignPhase,
    ): GameResource {
        $changeDesignPhase->handle($request->user(), $game, $request->designPhase());

        return GameResource::make($game->loadCount('versions'));
    }
}
