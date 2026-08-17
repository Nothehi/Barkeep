<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\CompleteGameFramework;
use Modules\DesignFramework\Application\Commands\PauseGameFramework;
use Modules\DesignFramework\Application\Commands\ResumeGameFramework;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving a game's adoption through its lifecycle.
 *
 * Pausing is the interesting one. It exists so that stepping away can be honest: without it, a studio
 * that stops working a methodology for a month either leaves every progress bar claiming active work
 * or completes a framework it barely started.
 */
class GameFrameworkLifecycleController extends Controller
{
    /**
     * Step away from the framework for a while.
     */
    public function pause(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        PauseGameFramework $pause,
    ): RedirectResponse {
        $pause->handle($request->user(), $this->adoption($game, $getGameFramework, 'pause'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework paused.')]);

        return back();
    }

    /**
     * Pick the framework back up.
     */
    public function resume(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        ResumeGameFramework $resume,
    ): RedirectResponse {
        $resume->handle($request->user(), $this->adoption($game, $getGameFramework, 'resume'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework resumed.')]);

        return back();
    }

    /**
     * Declare the game finished with its framework.
     */
    public function complete(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        CompleteGameFramework $complete,
    ): RedirectResponse {
        $complete->handle($request->user(), $this->adoption($game, $getGameFramework, 'complete'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework completed.')]);

        return back();
    }

    /**
     * Resolve and authorize the game's adoption.
     */
    private function adoption(Game $game, GetGameFramework $getGameFramework, string $ability): GameFramework
    {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize($ability, $adoption);

        return $adoption;
    }
}
