<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateMechanic;
use Modules\GameRules\Application\Commands\DeleteMechanic;
use Modules\GameRules\Application\Commands\ReorderMechanics;
use Modules\GameRules\Application\Commands\UpdateMechanic;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateMechanicRequest;
use Modules\GameRules\Presentation\Http\Requests\ReorderMechanicsRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateMechanicRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The mechanisms a rule system says it uses.
 *
 * Every write answers with a redirect rather than JSON, so the reloaded page
 * brings the new record, the recomputed counts and the refreshed findings
 * together. That matters more here than in most modules: the rules dashboard
 * shows the same rule set eight ways at once, and almost every write moves
 * several of them — adding a transition can turn an unreachable phase into a
 * reachable one three sections further down.
 */
class RuleMechanicController extends Controller
{
    /**
     * Name a mechanism this rule system uses.
     */
    public function store(
        CreateMechanicRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateMechanic $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mechanic added.')]);

        return back();
    }

    /**
     * Rename a mechanism or recategorise it.
     */
    public function update(
        UpdateMechanicRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleMechanic $ruleMechanic,
        UpdateMechanic $update,
    ): RedirectResponse {
        $update->handle($request->user(), $ruleMechanic, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mechanic updated.')]);

        return back();
    }

    /**
     * Stop claiming the rule system uses a mechanism.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleMechanic $ruleMechanic,
        DeleteMechanic $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $ruleMechanic);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mechanic removed.')]);

        return back();
    }

    /**
     * Put them into the order the designer dragged them into.
     */
    public function reorder(
        ReorderMechanicsRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ReorderMechanics $reorder,
    ): RedirectResponse {
        $reorder->handle($request->user(), $ruleSet, $request->orderedIds());

        return back();
    }
}
