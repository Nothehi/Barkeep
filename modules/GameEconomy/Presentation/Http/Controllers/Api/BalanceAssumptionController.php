<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceAssumption;
use Modules\GameEconomy\Application\Commands\UpdateBalanceAssumption;
use Modules\GameEconomy\Application\Queries\GetBalanceAssumptions;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceAssumptionRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceAssumptionRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceAssumptionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Why the numbers are what they are.
 *
 * There is no delete route. An assumption that turned out to be wrong is the
 * most useful entry in the list — it is the one that explains why the numbers
 * changed — so it is revised rather than removed.
 */
class BalanceAssumptionController extends Controller
{
    /**
     * List the beliefs behind the configuration, least confident first.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceAssumptions $getAssumptions,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return BalanceAssumptionResource::collection($getAssumptions->handle($profile));
    }

    /**
     * Write down why a number is what it is.
     */
    public function store(
        CreateBalanceAssumptionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceAssumption $createAssumption,
    ): JsonResponse {
        $assumption = $createAssumption->handle($request->user(), $profile, $request->toData());

        return BalanceAssumptionResource::make($assumption)->response()->setStatusCode(201);
    }

    /**
     * Revise a belief, or change how strongly it is held.
     */
    public function update(
        UpdateBalanceAssumptionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceAssumption $assumption,
        UpdateBalanceAssumption $updateAssumption,
    ): BalanceAssumptionResource {
        $updateAssumption->handle($request->user(), $assumption, $request->toData());

        return BalanceAssumptionResource::make($assumption->load('creator'));
    }
}
