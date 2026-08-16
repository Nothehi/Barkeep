<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\AddParticipant;
use Modules\Playtesting\Application\Commands\RemoveParticipant;
use Modules\Playtesting\Application\Queries\GetParticipants;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\AddParticipantRequest;
use Modules\Playtesting\Presentation\Http\Requests\RemoveParticipantRequest;
use Modules\Playtesting\Presentation\Http\Resources\ParticipantResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The people at a session.
 *
 * Most of them have no Barkeep account, so the endpoint is shaped around a
 * name rather than around an identifier. See {@see AddParticipantRequest} for
 * why an account, when given, has to be one the caller already shares a
 * workspace with.
 */
class ParticipantController extends Controller
{
    /**
     * List who was at the session, in the order they were added.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        GetParticipants $getParticipants,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $session);

        return ParticipantResource::collection($getParticipants->handle($session));
    }

    /**
     * Seat somebody at the session.
     */
    public function store(
        AddParticipantRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        AddParticipant $addParticipant,
    ): JsonResponse {
        $participant = $addParticipant->handle($request->user(), $session, $request->toData());

        return ParticipantResource::make($participant->load('user'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Take somebody off the session.
     *
     * What they said, and what was noticed about them, survives with its
     * attribution dropped — see {@see RemoveParticipant}.
     */
    public function destroy(
        RemoveParticipantRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestParticipant $participant,
        RemoveParticipant $removeParticipant,
    ): JsonResponse {
        $removeParticipant->handle($request->user(), $session, $participant);

        return response()->json(status: 204);
    }
}
