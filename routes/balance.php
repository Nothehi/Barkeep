<?php

use Illuminate\Support\Facades\Route;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceActionController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceActionLineController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceAnalysisController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceAssumptionController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceEffectController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceFlowController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceObservationController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceProfileLifecycleController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceResourceController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceScenarioController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceSnapshotController;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\BalanceVariableController;

/*
|--------------------------------------------------------------------------
| Balance screens
|--------------------------------------------------------------------------
|
| A balance configuration belongs to a *design version*, not to a game, so every URL here carries
| the whole chain:
|
|     /app/workspaces/prototype-lab/games/bears-and-bridges/versions/4/balance/{profile}/…
|
| That version segment is the module's foundational decision rather than a routing detail. Wood
| income was 2 in v1 and 3 in v2, and a URL that named only the game would have no way to say which
| of those it meant — which is exactly the ambiguity that makes historical playtests
| uninterpretable.
|
| The nesting is not decoration and it is not REST orthodoxy either. Each segment is resolved
| *through* the one before it by explicit bindings registered in GameEconomyServiceProvider — a
| profile through its version, a resource through its profile, a cost through its action, an
| override through its scenario. So a variable id belonging to somebody else's configuration does
| not 403; it fails to resolve, and the request 404s before a handler runs.
|
| That is what lets these ids be opaque uuids in a URL without any of them being a capability. It
| is also why the children are not exposed at shorter top-level addresses — `/balance-profiles/{id}`,
| `/economy-actions/{id}` — as a flatter API design would suggest. Reaching a profile without its
| version would mean looking the parent up *from* the child, which is exactly the reverse-lookup
| pattern that turns a guessed id into cross-workspace access. Playtesting and PrototypeIteration
| made the same call for the same reason; see the notes at the top of routes/playtests.php and
| routes/prototypes.php.
|
| `{version}` is GameDesign's binding, reused rather than re-declared. Route binder names are global
| to the application, and a second claim on that one would break both GameDesign's own chain and
| DesignFramework's delegation through it. Three other names here were chosen around the same table:
| `{economyAction}` rather than `{action}`, `{resourceType}` rather than `{resource}`, and
| `{balanceObservation}` rather than `{observation}` — the last because `{observation}` belongs to
| Playtesting and binding it here would break every playtest evidence route in the application. See
| `.ai/rules/providers.md`.
|
| Lifecycle changes are POSTs to named actions rather than a PATCH of a status field, because they
| are actions with rules — activating a profile retires whichever was in play, and archiving cannot
| be undone — rather than editable attributes.
|
| Two routes are worth pointing at:
|
| - `snapshots/compare` takes its two snapshots as query parameters rather than as path segments,
|   because a comparison is a question about two records rather than a record that owns another.
|   Both are still resolved through the profile in the controller, so the ownership rule has no
|   exception.
| - `analysis` is a POST that changes nothing. It exists because pressing "Analyse" is a fact about
|   how a studio works, and the dashboard's own reading of the same numbers deliberately does not
|   announce itself.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('workspaces/{workspace}/games/{game}/versions/{version}/balance')->group(function () {
        Route::get('/', [BalanceController::class, 'index'])->name('balance.index');
        Route::post('/', [BalanceController::class, 'store'])->name('balance.store');

        Route::prefix('{profile}')->group(function () {
            Route::get('/', [BalanceController::class, 'show'])->name('balance.show');
            Route::patch('/', [BalanceController::class, 'update'])->name('balance.update');

            Route::post('activate', [BalanceProfileLifecycleController::class, 'activate'])->name('balance.activate');
            Route::post('archive', [BalanceProfileLifecycleController::class, 'archive'])->name('balance.archive');

            /*
             * Resources: what players hold and spend.
             */
            Route::post('resources', [BalanceResourceController::class, 'store'])->name('balance.resources.store');
            Route::get('resources/{resourceType}', [BalanceResourceController::class, 'show'])->name('balance.resources.show');
            Route::patch('resources/{resourceType}', [BalanceResourceController::class, 'update'])->name('balance.resources.update');
            Route::delete('resources/{resourceType}', [BalanceResourceController::class, 'destroy'])->name('balance.resources.destroy');

            /*
             * Flows: how those resources move.
             */
            Route::post('flows', [BalanceFlowController::class, 'store'])->name('balance.flows.store');
            Route::patch('flows/{flow}', [BalanceFlowController::class, 'update'])->name('balance.flows.update');
            Route::delete('flows/{flow}', [BalanceFlowController::class, 'destroy'])->name('balance.flows.destroy');

            /*
             * Actions, and everything they do.
             */
            Route::post('actions', [BalanceActionController::class, 'store'])->name('balance.actions.store');

            Route::prefix('actions/{economyAction}')->group(function () {
                Route::get('/', [BalanceActionController::class, 'show'])->name('balance.actions.show');
                Route::patch('/', [BalanceActionController::class, 'update'])->name('balance.actions.update');
                Route::delete('/', [BalanceActionController::class, 'destroy'])->name('balance.actions.destroy');

                Route::post('costs', [BalanceActionLineController::class, 'storeCost'])->name('balance.actions.costs.store');
                Route::patch('costs/{cost}', [BalanceActionLineController::class, 'updateCost'])->name('balance.actions.costs.update');
                Route::delete('costs/{cost}', [BalanceActionLineController::class, 'destroyCost'])->name('balance.actions.costs.destroy');

                Route::post('rewards', [BalanceActionLineController::class, 'storeReward'])->name('balance.actions.rewards.store');
                Route::patch('rewards/{reward}', [BalanceActionLineController::class, 'updateReward'])->name('balance.actions.rewards.update');
                Route::delete('rewards/{reward}', [BalanceActionLineController::class, 'destroyReward'])->name('balance.actions.rewards.destroy');

                Route::post('effects', [BalanceEffectController::class, 'store'])->name('balance.actions.effects.store');
                Route::patch('effects/{effect}', [BalanceEffectController::class, 'update'])->name('balance.actions.effects.update');
                Route::delete('effects/{effect}', [BalanceEffectController::class, 'destroy'])->name('balance.actions.effects.destroy');
            });

            /*
             * Variables: the numbers a designer tunes.
             */
            Route::post('variables', [BalanceVariableController::class, 'store'])->name('balance.variables.store');
            Route::patch('variables/{variable}', [BalanceVariableController::class, 'update'])->name('balance.variables.update');
            Route::delete('variables/{variable}', [BalanceVariableController::class, 'destroy'])->name('balance.variables.destroy');

            /*
             * Scenarios, and the values they state differently.
             */
            Route::post('scenarios', [BalanceScenarioController::class, 'store'])->name('balance.scenarios.store');

            Route::prefix('scenarios/{scenario}')->group(function () {
                Route::patch('/', [BalanceScenarioController::class, 'update'])->name('balance.scenarios.update');
                Route::post('archive', [BalanceScenarioController::class, 'archive'])->name('balance.scenarios.archive');

                Route::post('variables', [BalanceScenarioController::class, 'storeVariable'])->name('balance.scenarios.variables.store');
                Route::delete('variables/{override}', [BalanceScenarioController::class, 'destroyVariable'])->name('balance.scenarios.variables.destroy');
            });

            /*
             * The record of why the numbers are what they are, and what the studio saw.
             */
            Route::post('assumptions', [BalanceAssumptionController::class, 'store'])->name('balance.assumptions.store');
            Route::patch('assumptions/{assumption}', [BalanceAssumptionController::class, 'update'])->name('balance.assumptions.update');

            Route::post('observations', [BalanceObservationController::class, 'store'])->name('balance.observations.store');
            Route::patch('observations/{balanceObservation}', [BalanceObservationController::class, 'update'])->name('balance.observations.update');

            /*
             * Analysis and history.
             */
            Route::post('analysis', [BalanceAnalysisController::class, 'store'])->name('balance.analysis.store');

            Route::post('snapshots', [BalanceSnapshotController::class, 'store'])->name('balance.snapshots.store');
            Route::get('snapshots/compare', [BalanceSnapshotController::class, 'compare'])->name('balance.snapshots.compare');
        });
    });
});
