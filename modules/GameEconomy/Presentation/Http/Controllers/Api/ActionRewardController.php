<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AddActionReward;
use Modules\GameEconomy\Application\Commands\RemoveActionReward;
use Modules\GameEconomy\Application\Commands\UpdateActionReward;
use Modules\GameEconomy\Application\Queries\GetActionRewards;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Presentation\Http\Requests\AddActionLineRequest;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateActionLineRequest;
use Modules\GameEconomy\Presentation\Http\Resources\ActionRewardResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What an action pays out.
 */
class ActionRewardController extends Controller
{
    /**
     * List what the action pays.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        GetActionRewards $getRewards,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return ActionRewardResource::collection($getRewards->handle($economyAction));
    }

    /**
     * Have the action pay out a resource.
     */
    public function store(
        AddActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        AddActionReward $addReward,
    ): JsonResponse {
        $reward = $addReward->handle($request->user(), $economyAction, $request->toData());

        return ActionRewardResource::make($reward)->response()->setStatusCode(201);
    }

    /**
     * Retune what the action pays.
     */
    public function update(
        UpdateActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionReward $reward,
        UpdateActionReward $updateReward,
    ): ActionRewardResource {
        $updateReward->handle($request->user(), $reward, $request->toData());

        return ActionRewardResource::make($reward->load('resource'));
    }

    /**
     * Stop the action paying out a resource.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionReward $reward,
        RemoveActionReward $removeReward,
    ): JsonResponse {
        $removeReward->handle($request->user(), $reward);

        return response()->json(status: 204);
    }
}
