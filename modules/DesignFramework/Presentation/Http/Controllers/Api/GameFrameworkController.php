<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\AssignFrameworkToGame;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Presentation\Http\Requests\AssignFrameworkRequest;
use Modules\DesignFramework\Presentation\Http\Resources\GameFrameworkResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The methodology a game follows.
 *
 * A singleton sub-resource rather than a collection, because a game follows one framework at a time —
 * see section 46. `GET` answers 404 when the game follows none, which is an honest answer to "show me
 * this game's framework" and is what lets the screen offer to adopt one.
 *
 * The version arrives in the body rather than in the URL, and it is the only identifier on this side
 * of the module that does. A game adopting a framework is choosing from every published edition on
 * the platform, so there is no parent segment to resolve the choice through — which is why
 * `AssignFrameworkToGame` proves the version is adoptable itself.
 */
class GameFrameworkController extends Controller
{
    /**
     * Show the framework this game follows.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
    ): GameFrameworkResource {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize('view', $adoption);

        return GameFrameworkResource::make($adoption);
    }

    /**
     * Adopt a framework version for this game.
     *
     * The version is resolved with an unscoped lookup, which is the one place in the module that
     * happens — and it is safe because framework versions are platform-wide rather than a studio's:
     * every published edition is meant to be adoptable by anybody. The command refuses drafts and
     * archived editions, so an id that names something not yet public is turned away there.
     */
    public function store(
        AssignFrameworkRequest $request,
        Workspace $workspace,
        Game $game,
        AssignFrameworkToGame $assignFramework,
    ): JsonResponse {
        $version = FrameworkVersion::query()
            ->with('framework')
            ->findOrFail($request->frameworkVersionId());

        $adoption = $assignFramework->handle($request->user(), $game, $version);

        return GameFrameworkResource::make($adoption)
            ->response()
            ->setStatusCode(201);
    }
}
