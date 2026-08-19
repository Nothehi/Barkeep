<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceAssumption;
use Modules\GameEconomy\Application\Commands\UpdateBalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceAssumptionRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceAssumptionRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Why the numbers are what they are.
 *
 * There is no destroy route, and the absence is the point: an assumption that
 * turned out to be wrong is the most useful entry in the list, because it is the
 * one that explains why the numbers changed.
 */
class BalanceAssumptionController extends Controller
{
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
    ): RedirectResponse {
        $createAssumption->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assumption recorded.')]);

        return back();
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
    ): RedirectResponse {
        $updateAssumption->handle($request->user(), $assumption, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assumption updated.')]);

        return back();
    }
}
