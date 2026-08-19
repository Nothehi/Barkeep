<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateResourceFlow;
use Modules\GameEconomy\Application\Commands\DeleteResourceFlow;
use Modules\GameEconomy\Application\Commands\UpdateResourceFlow;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateResourceFlowRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateResourceFlowRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The declared movements of a configuration's resources.
 */
class BalanceFlowController extends Controller
{
    /**
     * Declare a way a resource moves.
     */
    public function store(
        CreateResourceFlowRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateResourceFlow $createFlow,
    ): RedirectResponse {
        $createFlow->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Flow added.')]);

        return back();
    }

    /**
     * Retune a declared movement.
     */
    public function update(
        UpdateResourceFlowRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceFlow $flow,
        UpdateResourceFlow $updateFlow,
    ): RedirectResponse {
        $updateFlow->handle($request->user(), $flow, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Flow updated.')]);

        return back();
    }

    /**
     * Remove a declared movement.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceFlow $flow,
        DeleteResourceFlow $deleteFlow,
    ): RedirectResponse {
        $deleteFlow->handle($request->user(), $flow);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Flow removed.')]);

        return back();
    }
}
