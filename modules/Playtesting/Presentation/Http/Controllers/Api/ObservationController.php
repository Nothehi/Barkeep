<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CreateObservation;
use Modules\Playtesting\Application\Commands\DeleteObservation;
use Modules\Playtesting\Application\Commands\UpdateObservation;
use Modules\Playtesting\Application\Queries\GetObservations;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CreateObservationRequest;
use Modules\Playtesting\Presentation\Http\Requests\DeleteObservationRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdateObservationRequest;
use Modules\Playtesting\Presentation\Http\Resources\ObservationResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What a designer noticed during a session.
 *
 * The most-used endpoint in the module, and the one whose latency is felt: an
 * observation is typed while the game carries on, so this exists to be quick
 * rather than complete.
 */
class ObservationController extends Controller
{
    /**
     * List what was noticed, in the order it was noticed.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        GetObservations $getObservations,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $session);

        return ObservationResource::collection($getObservations->handle($session));
    }

    /**
     * Record something noticed.
     */
    public function store(
        CreateObservationRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CreateObservation $createObservation,
    ): JsonResponse {
        $observation = $createObservation->handle($request->user(), $session, $request->toData());

        return ObservationResource::make($observation->load(['participant', 'creator']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Correct an observation while the session is still open.
     */
    public function update(
        UpdateObservationRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestObservation $observation,
        UpdateObservation $updateObservation,
    ): ObservationResource {
        $updateObservation->handle($request->user(), $session, $observation, $request->toData());

        return ObservationResource::make($observation->load(['participant', 'creator']));
    }

    /**
     * Withdraw an observation while the session is still open.
     */
    public function destroy(
        DeleteObservationRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestObservation $observation,
        DeleteObservation $deleteObservation,
    ): JsonResponse {
        $deleteObservation->handle($request->user(), $session, $observation);

        return response()->json(status: 204);
    }
}
