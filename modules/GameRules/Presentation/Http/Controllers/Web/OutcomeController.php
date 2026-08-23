<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateDefeatCondition;
use Modules\GameRules\Application\Commands\CreateGameEndCondition;
use Modules\GameRules\Application\Commands\CreateVictoryCondition;
use Modules\GameRules\Application\Commands\DeleteDefeatCondition;
use Modules\GameRules\Application\Commands\DeleteGameEndCondition;
use Modules\GameRules\Application\Commands\DeleteVictoryCondition;
use Modules\GameRules\Application\Commands\UpdateDefeatCondition;
use Modules\GameRules\Application\Commands\UpdateGameEndCondition;
use Modules\GameRules\Application\Commands\UpdateVictoryCondition;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Presentation\Http\Requests\CreateOutcomeRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateOutcomeRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * How a game is won, lost, and brought to a close.
 *
 * One controller for three kinds of outcome, because they are edited on one
 * screen and by one form. The three *records* stay separate — winning, losing and
 * stopping are three different questions a game answers at once — but a controller
 * per kind would be three copies of nine identical methods.
 *
 * Nine methods rather than three with a discriminator, so each route names what it
 * does and no request body can decide which table it lands in.
 */
class OutcomeController extends Controller
{
    /**
     * Record a way to win.
     */
    public function storeVictory(
        CreateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateVictoryCondition $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Victory condition added.')]);

        return back();
    }

    /**
     * Reword a way to win, or attach a condition to it.
     */
    public function updateVictory(
        UpdateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        VictoryCondition $victoryCondition,
        UpdateVictoryCondition $update,
    ): RedirectResponse {
        $update->handle($request->user(), $victoryCondition, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Victory condition updated.')]);

        return back();
    }

    /**
     * Remove a way to win.
     */
    public function destroyVictory(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        VictoryCondition $victoryCondition,
        DeleteVictoryCondition $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $victoryCondition);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Victory condition removed.')]);

        return back();
    }

    /**
     * Record a way to be knocked out.
     */
    public function storeDefeat(
        CreateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateDefeatCondition $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Defeat condition added.')]);

        return back();
    }

    /**
     * Reword a way to be knocked out.
     */
    public function updateDefeat(
        UpdateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        DefeatCondition $defeatCondition,
        UpdateDefeatCondition $update,
    ): RedirectResponse {
        $update->handle($request->user(), $defeatCondition, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Defeat condition updated.')]);

        return back();
    }

    /**
     * Remove a way to be knocked out.
     */
    public function destroyDefeat(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        DefeatCondition $defeatCondition,
        DeleteDefeatCondition $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $defeatCondition);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Defeat condition removed.')]);

        return back();
    }

    /**
     * Record something that ends the game.
     */
    public function storeEnd(
        CreateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateGameEndCondition $create,
    ): RedirectResponse {
        $create->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('End condition added.')]);

        return back();
    }

    /**
     * Reword something that ends the game.
     */
    public function updateEnd(
        UpdateOutcomeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameEndCondition $endCondition,
        UpdateGameEndCondition $update,
    ): RedirectResponse {
        $update->handle($request->user(), $endCondition, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('End condition updated.')]);

        return back();
    }

    /**
     * Remove something that ends the game.
     */
    public function destroyEnd(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameEndCondition $endCondition,
        DeleteGameEndCondition $delete,
    ): RedirectResponse {
        $delete->handle($request->user(), $endCondition);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('End condition removed.')]);

        return back();
    }
}
