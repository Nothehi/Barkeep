<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceVariable;
use Modules\GameEconomy\Application\Commands\DeleteBalanceVariable;
use Modules\GameEconomy\Application\Commands\UpdateBalanceVariable;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceVariableRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceVariableRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The numbers a configuration exposes for tuning.
 *
 * `update` is what the variable table's inline editing writes through. It
 * answers with `back()` like every other write here, which is what keeps the
 * table, the analysis summary and the warnings list agreeing after a cell
 * changes — a value edited in place moves all three, and splicing the new number
 * into a local array would leave the other two describing the old one.
 */
class BalanceVariableController extends Controller
{
    /**
     * Expose a number for tuning.
     */
    public function store(
        CreateBalanceVariableRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceVariable $createVariable,
    ): RedirectResponse {
        $createVariable->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variable added.')]);

        return back();
    }

    /**
     * Change a tunable number.
     */
    public function update(
        UpdateBalanceVariableRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceVariable $variable,
        UpdateBalanceVariable $updateVariable,
    ): RedirectResponse {
        $updateVariable->handle($request->user(), $variable, $request->toData());

        return back();
    }

    /**
     * Remove a tunable number, and every scenario override of it.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceVariable $variable,
        DeleteBalanceVariable $deleteVariable,
    ): RedirectResponse {
        $deleteVariable->handle($request->user(), $variable);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Variable removed.')]);

        return back();
    }
}
