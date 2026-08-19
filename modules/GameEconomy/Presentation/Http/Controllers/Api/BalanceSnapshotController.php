<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceSnapshot;
use Modules\GameEconomy\Application\Queries\GetBalanceSnapshots;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceSnapshotRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceSnapshotResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A configuration's frozen states.
 *
 * There is no update route and no delete route, and both absences are the
 * immutability rule. A snapshot is what the economy was; rewriting one would
 * change what every playtest run against it was played under, and deleting one
 * would remove the only record of it.
 */
class BalanceSnapshotController extends Controller
{
    /**
     * List the configuration's frozen states, newest first.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceSnapshots $getSnapshots,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return BalanceSnapshotResource::collection($getSnapshots->handle($profile));
    }

    /**
     * Freeze the configuration as it stands.
     */
    public function store(
        CreateBalanceSnapshotRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceSnapshot $createSnapshot,
    ): JsonResponse {
        $snapshot = $createSnapshot->handle($request->user(), $profile, $request->toData());

        return BalanceSnapshotResource::make($snapshot)->response()->setStatusCode(201);
    }
}
