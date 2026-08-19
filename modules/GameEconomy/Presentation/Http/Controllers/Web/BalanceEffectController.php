<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AddActionEffect;
use Modules\GameEconomy\Application\Commands\RemoveActionEffect;
use Modules\GameEconomy\Application\Commands\UpdateActionEffect;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Presentation\Http\Requests\AddActionEffectRequest;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateActionEffectRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What an action does beyond moving resources.
 */
class BalanceEffectController extends Controller
{
    /**
     * Record something the action does beyond resources.
     */
    public function store(
        AddActionEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        AddActionEffect $addEffect,
    ): RedirectResponse {
        $addEffect->handle($request->user(), $economyAction, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Effect added.')]);

        return back();
    }

    /**
     * Change one of the action's effects.
     */
    public function update(
        UpdateActionEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionEffect $effect,
        UpdateActionEffect $updateEffect,
    ): RedirectResponse {
        $updateEffect->handle($request->user(), $effect, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Effect updated.')]);

        return back();
    }

    /**
     * Remove one of the action's effects.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionEffect $effect,
        RemoveActionEffect $removeEffect,
    ): RedirectResponse {
        $removeEffect->handle($request->user(), $effect);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Effect removed.')]);

        return back();
    }
}
