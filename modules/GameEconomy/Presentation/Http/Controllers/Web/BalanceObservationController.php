<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceObservation;
use Modules\GameEconomy\Application\Commands\UpdateBalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceObservationRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceObservationRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What the studio noticed about the economy.
 *
 * These are the balance interpretation of evidence rather than the evidence —
 * Playtesting owns what happened at the table, and nothing here reaches for it.
 */
class BalanceObservationController extends Controller
{
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
    ): RedirectResponse {
        $createObservation->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Observation recorded.')]);

        return back();
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
    ): RedirectResponse {
        $updateObservation->handle($request->user(), $balanceObservation, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Observation updated.')]);

        return back();
    }
}
