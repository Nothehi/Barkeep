<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AddActionCost;
use Modules\GameEconomy\Application\Commands\AddActionReward;
use Modules\GameEconomy\Application\Commands\RemoveActionCost;
use Modules\GameEconomy\Application\Commands\RemoveActionReward;
use Modules\GameEconomy\Application\Commands\UpdateActionCost;
use Modules\GameEconomy\Application\Commands\UpdateActionReward;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Presentation\Http\Requests\AddActionLineRequest;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateActionLineRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What an action takes, and what it gives back.
 *
 * One controller for both, unlike the API where each has its own. The action
 * page edits them side by side in two panels of the same form, and every one of
 * these methods answers with `back()` — so splitting them would produce two
 * files whose only difference is which command they call.
 *
 * The commands stay separate, because that is where the two actually differ.
 */
class BalanceActionLineController extends Controller
{
    /**
     * Price the action in a resource.
     */
    public function storeCost(
        AddActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        AddActionCost $addCost,
    ): RedirectResponse {
        $addCost->handle($request->user(), $economyAction, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cost added.')]);

        return back();
    }

    /**
     * Retune what the action costs.
     */
    public function updateCost(
        UpdateActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionCost $cost,
        UpdateActionCost $updateCost,
    ): RedirectResponse {
        $updateCost->handle($request->user(), $cost, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cost updated.')]);

        return back();
    }

    /**
     * Stop the action costing a resource.
     */
    public function destroyCost(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionCost $cost,
        RemoveActionCost $removeCost,
    ): RedirectResponse {
        $removeCost->handle($request->user(), $cost);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cost removed.')]);

        return back();
    }

    /**
     * Have the action pay out a resource.
     */
    public function storeReward(
        AddActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        AddActionReward $addReward,
    ): RedirectResponse {
        $addReward->handle($request->user(), $economyAction, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reward added.')]);

        return back();
    }

    /**
     * Retune what the action pays.
     */
    public function updateReward(
        UpdateActionLineRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionReward $reward,
        UpdateActionReward $updateReward,
    ): RedirectResponse {
        $updateReward->handle($request->user(), $reward, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reward updated.')]);

        return back();
    }

    /**
     * Stop the action paying out a resource.
     */
    public function destroyReward(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionReward $reward,
        RemoveActionReward $removeReward,
    ): RedirectResponse {
        $removeReward->handle($request->user(), $reward);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reward removed.')]);

        return back();
    }
}
