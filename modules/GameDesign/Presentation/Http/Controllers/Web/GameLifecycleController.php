<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Application\Commands\ChangeDesignPhase;
use Modules\GameDesign\Application\Commands\ChangeGameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Requests\ChangeDesignPhaseRequest;
use Modules\GameDesign\Presentation\Http\Requests\ChangeGameStatusRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The explicit lifecycle actions a game offers.
 *
 * Each of these is reached by pressing a named button — "Start designing",
 * "Put on hold", "Mark complete" — rather than by editing a field. Which
 * buttons exist is decided by the transition matrix and sent to the client on
 * the game itself, so the interface can only ever offer moves the domain
 * would accept.
 */
class GameLifecycleController extends Controller
{
    /**
     * Move the game to the requested status.
     */
    public function changeStatus(
        ChangeGameStatusRequest $request,
        Workspace $workspace,
        Game $game,
        ChangeGameStatus $changeStatus,
    ): RedirectResponse {
        $changeStatus->handle($request->user(), $game, $request->status());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game is now :status.', ['status' => mb_strtolower($game->status->label())]),
        ]);

        return back();
    }

    /**
     * Record the game's design phase.
     */
    public function changeDesignPhase(
        ChangeDesignPhaseRequest $request,
        Workspace $workspace,
        Game $game,
        ChangeDesignPhase $changeDesignPhase,
    ): RedirectResponse {
        $changeDesignPhase->handle($request->user(), $game, $request->designPhase());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Design phase updated.'),
        ]);

        return back();
    }
}
