<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\CompleteGameFramework;
use Modules\DesignFramework\Application\Commands\PauseGameFramework;
use Modules\DesignFramework\Application\Commands\ResumeGameFramework;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Presentation\Http\Resources\GameFrameworkResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving a game's adoption through its lifecycle.
 *
 * Three POSTs to named actions rather than a PATCH of the status field, because they are actions with
 * rules rather than an editable attribute — and because "complete" is a claim a designer makes about
 * their own work rather than a value.
 *
 * Pausing and resuming exist as a pair. Without resume, pausing would be a one-way door and a studio
 * that stepped away for a month could only get back by declaring itself finished with a methodology it
 * had barely started.
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
    ): GameFrameworkResource {
        $adoption = $this->adoption($game, $getGameFramework, 'pause');

        $pause->handle($request->user(), $adoption);

        return GameFrameworkResource::make($adoption);
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
    ): GameFrameworkResource {
        $adoption = $this->adoption($game, $getGameFramework, 'resume');

        $resume->handle($request->user(), $adoption);

        return GameFrameworkResource::make($adoption);
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
    ): GameFrameworkResource {
        $adoption = $this->adoption($game, $getGameFramework, 'complete');

        $complete->handle($request->user(), $adoption);

        return GameFrameworkResource::make($adoption);
    }

    /**
     * Resolve and authorize the game's adoption.
     *
     * The adoption is found through the game rather than named in the URL, so there is no id a caller
     * could substitute. A game that follows nothing 404s before the ability is checked, because
     * "there is no framework here" is a better answer than "you may not pause it".
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
