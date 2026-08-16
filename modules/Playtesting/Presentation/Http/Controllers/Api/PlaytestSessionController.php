<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CreatePlaytestSession;
use Modules\Playtesting\Application\Commands\UpdatePlaytestSession;
use Modules\Playtesting\Application\Queries\GetSessions;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CreateSessionRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdateSessionRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSessionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A playtest's sittings.
 *
 * Listed earliest first, because sessions are read as a sequence: "by the
 * third group they had stopped asking about scoring" only makes sense in
 * order.
 */
class PlaytestSessionController extends Controller
{
    /**
     * List the playtest's sessions.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        GetSessions $getSessions,
    ): AnonymousResourceCollection {
        Gate::authorize('viewSessions', $playtest);

        return PlaytestSessionResource::collection($getSessions->handle($playtest));
    }

    /**
     * Schedule another sitting.
     */
    public function store(
        CreateSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        CreatePlaytestSession $createSession,
    ): JsonResponse {
        $session = $createSession->handle($request->user(), $playtest, $request->toData());

        return PlaytestSessionResource::make($session->loadCount(['participants', 'observations', 'feedback']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show one sitting.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
    ): PlaytestSessionResource {
        Gate::authorize('view', $session);

        return PlaytestSessionResource::make(
            $session->load('creator')->loadCount(['participants', 'observations', 'feedback']),
        );
    }

    /**
     * Change a sitting that has not ended.
     */
    public function update(
        UpdateSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        UpdatePlaytestSession $updateSession,
    ): PlaytestSessionResource {
        $updateSession->handle($request->user(), $session, $request->toData());

        return PlaytestSessionResource::make(
            $session->load('creator')->loadCount(['participants', 'observations', 'feedback']),
        );
    }
}
