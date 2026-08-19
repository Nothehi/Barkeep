<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AddActionCost;
use Modules\GameEconomy\Application\Commands\RemoveActionCost;
use Modules\GameEconomy\Application\Commands\UpdateActionCost;
use Modules\GameEconomy\Application\Queries\GetActionCosts;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Presentation\Http\Requests\AddActionLineRequest;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateActionLineRequest;
use Modules\GameEconomy\Presentation\Http\Resources\ActionCostResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What an action takes to perform.
 */
class ActionCostController extends Controller
{
    /**
     * List what the action costs.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        GetActionCosts $getCosts,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return ActionCostResource::collection($getCosts->handle($economyAction));
    }

    /**
     * Price the action in a resource.
     */
    public function store(
        AddActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        AddActionCost $addCost,
    ): JsonResponse {
        $cost = $addCost->handle($request->user(), $economyAction, $request->toData());

        return ActionCostResource::make($cost)->response()->setStatusCode(201);
    }

    /**
     * Retune what the action costs.
     */
    public function update(
        UpdateActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionCost $cost,
        UpdateActionCost $updateCost,
    ): ActionCostResource {
        $updateCost->handle($request->user(), $cost, $request->toData());

        return ActionCostResource::make($cost->load('resource'));
    }

    /**
     * Stop the action costing a resource.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionCost $cost,
        RemoveActionCost $removeCost,
    ): JsonResponse {
        $removeCost->handle($request->user(), $cost);

        return response()->json(status: 204);
    }
}
