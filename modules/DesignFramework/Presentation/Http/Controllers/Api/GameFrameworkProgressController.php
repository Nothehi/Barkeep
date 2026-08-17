<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Queries\GetGameFrameworkProgress;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkProgressResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * How far a game has got through its methodology.
 *
 * Its own endpoint rather than a field on the adoption, because it is counted from the version's whole
 * content and the game's whole record — several queries — and the screens that want the adoption
 * usually do not want the arithmetic.
 *
 * Nothing is cached. Every figure is counted on read, so a percentage can never disagree with the rows
 * it came from.
 */
class GameFrameworkProgressController extends Controller
{
    /**
     * Show the game's framework progress.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        GetGameFrameworkProgress $getProgress,
    ): FrameworkProgressResource {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize('view', $adoption);

        return FrameworkProgressResource::make($getProgress->handle($adoption));
    }
}
