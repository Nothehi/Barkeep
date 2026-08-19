<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceObservation;
use Modules\GameEconomy\Application\Commands\UpdateBalanceObservation;
use Modules\GameEconomy\Application\Queries\GetBalanceObservations;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceObservationRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceObservationRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceObservationResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What the studio noticed about the economy, worst first.
 *
 * These are the balance interpretation of evidence rather than the evidence
 * itself — Playtesting owns what happened at the table. There is no delete route
 * here either: an observation that turned out to be wrong is part of how the
 * studio arrived at the numbers.
 */
class BalanceObservationController extends Controller
{
    /**
     * List what the studio noticed.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceObservations $getObservations,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return BalanceObservationResource::collection($getObservations->handle($profile));
    }

    /**
     * Record what the studio noticed.
     */
    public function store(
        CreateBalanceObservationRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceObservation $createObservation,
    ): JsonResponse {
        $observation = $createObservation->handle($request->user(), $profile, $request->toData());

        return BalanceObservationResource::make($observation)->response()->setStatusCode(201);
    }

    /**
     * Revise what the studio noticed, or how badly it reads.
     */
    public function update(
        UpdateBalanceObservationRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceObservation $balanceObservation,
        UpdateBalanceObservation $updateObservation,
    ): BalanceObservationResource {
        $updateObservation->handle($request->user(), $balanceObservation, $request->toData());

        return BalanceObservationResource::make($balanceObservation->load('creator'));
    }
}
