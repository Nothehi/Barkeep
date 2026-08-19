<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\ArchiveBalanceScenario;
use Modules\GameEconomy\Application\Commands\CreateBalanceScenario;
use Modules\GameEconomy\Application\Commands\RemoveScenarioVariable;
use Modules\GameEconomy\Application\Commands\SetScenarioVariable;
use Modules\GameEconomy\Application\Commands\UpdateBalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceScenarioRequest;
use Modules\GameEconomy\Presentation\Http\Requests\SetScenarioVariableRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceScenarioRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The hypotheticals a configuration is read under, and the values they change.
 *
 * The overrides are handled here rather than in a controller of their own,
 * because they have no screen of their own: selecting a scenario on the
 * dashboard reveals its values inline, and setting one is a cell edit on that
 * panel.
 *
 * Every one of these answers with `back()`, and for the scenario panel that
 * matters more than usual: the point of the panel is comparing the override with
 * the base, so an override written into a local array would leave the two halves
 * of the comparison read from different moments.
 */
class BalanceScenarioController extends Controller
{
    /**
     * Name a situation to read the economy under.
     */
    public function store(
        CreateBalanceScenarioRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceScenario $createScenario,
    ): RedirectResponse {
        $createScenario->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scenario created.')]);

        return back();
    }

    /**
     * Rename a hypothetical, or say the studio is now reading against it.
     */
    public function update(
        UpdateBalanceScenarioRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        UpdateBalanceScenario $updateScenario,
    ): RedirectResponse {
        $updateScenario->handle($request->user(), $scenario, $request->toData(), $request->toStatus());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scenario updated.')]);

        return back();
    }

    /**
     * Put a hypothetical away.
     */
    public function archive(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        ArchiveBalanceScenario $archiveScenario,
    ): RedirectResponse {
        $archiveScenario->handle($request->user(), $scenario);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scenario archived.')]);

        return back();
    }

    /**
     * State a value differently under this hypothetical.
     *
     * Nothing here touches the base variable — the override is written to a
     * different table, so the guarantee holds by construction rather than by this
     * controller being careful.
     */
    public function storeVariable(
        SetScenarioVariableRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        SetScenarioVariable $setOverride,
    ): RedirectResponse {
        $setOverride->handle($request->user(), $scenario, $request->toData());

        return back();
    }

    /**
     * Stop this hypothetical stating a value differently.
     */
    public function destroyVariable(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        ScenarioVariable $override,
        RemoveScenarioVariable $removeOverride,
    ): RedirectResponse {
        $removeOverride->handle($request->user(), $override);

        return back();
    }
}
