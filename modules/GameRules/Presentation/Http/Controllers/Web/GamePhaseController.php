<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateGamePhase;
use Modules\GameRules\Application\Commands\DeleteGamePhase;
use Modules\GameRules\Application\Commands\ReorderGamePhases;
use Modules\GameRules\Application\Commands\UpdateGamePhase;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateGamePhaseRequest;
use Modules\GameRules\Presentation\Http\Requests\ReorderGamePhasesRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateGamePhaseRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The stages of play.
 *
 * Reordering these is the one reorder in the module that changes what the rules
 * *say*: a turn structure read out of sequence is a different turn structure, and
 * the graph takes the first phase as where play begins when none is marked as
 * setup.
 */
class GamePhaseController extends Controller
{
    /**
     * Add a stage of play.
     */
    public function store(
        CreateGamePhaseRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateGamePhase $createPhase,
    ): RedirectResponse {
        $createPhase->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Phase added.')]);

        return back();
    }

    /**
     * Rename a phase, retype it, or move it in the turn structure.
     */
    public function update(
        UpdateGamePhaseRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GamePhase $gamePhase,
        UpdateGamePhase $updatePhase,
    ): RedirectResponse {
        $updatePhase->handle($request->user(), $gamePhase, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Phase updated.')]);

        return back();
    }

    /**
     * Remove a phase, and the transitions into and out of it.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GamePhase $gamePhase,
        DeleteGamePhase $deletePhase,
    ): RedirectResponse {
        $deletePhase->handle($request->user(), $gamePhase);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Phase removed.')]);

        return back();
    }

    /**
     * Put the phases into the order play visits them.
     */
    public function reorder(
        ReorderGamePhasesRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ReorderGamePhases $reorder,
    ): RedirectResponse {
        $reorder->handle($request->user(), $ruleSet, $request->orderedIds());

        return back();
    }
}
