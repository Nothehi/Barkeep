<?php

use Illuminate\Support\Facades\Route;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationChangeController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationDecisionController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationEvidenceController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationExperimentController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationGameVersionController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationLifecycleController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\IterationPlaytestController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\PrototypeArtifactController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\PrototypeController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\PrototypeLifecycleController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\PrototypeVersionController;

/*
|--------------------------------------------------------------------------
| Prototype and iteration screens
|--------------------------------------------------------------------------
|
| Prototypes and iterations both live inside a game, which lives inside a workspace, and every URL
| here carries the whole chain:
|
|     /app/workspaces/prototype-lab/games/bears-and-bridges/iterations/{id}/…
|
| The nesting is not decoration and it is not REST orthodoxy either. Each segment is resolved
| *through* the one before it by explicit bindings registered in PrototypeIterationServiceProvider —
| a prototype through its game, a version through its prototype, an artifact through its version, a
| change or decision through its iteration. So a decision id belonging to somebody else's iteration
| does not 403; it fails to resolve, and the request 404s before a handler runs.
|
| That is what lets these ids be opaque uuids in a URL without any of them being a capability. It is
| also why the children are not exposed at shorter top-level addresses — `/iterations/{iteration}`,
| `/prototypes/{prototype}` — as a flatter API design would suggest. Reaching an iteration without
| its game would mean looking the parent up *from* the child, which is exactly the reverse-lookup
| pattern that turns a guessed id into cross-workspace access. Playtesting made the same call for the
| same reason; see the note at the top of routes/playtests.php.
|
| Prototypes and iterations are addressed by uuid. A game has an address somebody types and shares;
| an iteration is reached from the game's own screen, and a title like "Reduce combat downtime at
| four players" does not want to be a URL segment. Prototype *versions* are the exception: they are
| addressed by number, because "v3" is exactly what a designer says and a number is unique inside its
| prototype.
|
| Lifecycle changes are POSTs to named actions rather than a PATCH of a status field, because they
| are actions with rules — completing an iteration requires an outcome and a summary, and accepting a
| decision cannot be undone — rather than editable attributes.
|
| Two routes are worth pointing at:
|
| - attaching a playtest carries the playtest id in the body rather than in the URL, so that no route
|   in this module binds a model belonging to Playtesting. Detaching addresses the association
|   (`{link}`) for the same reason.
| - `iterations/{iteration}/game-version` is section 48's deliberate seam: the designer's explicit
|   decision that the design has moved on. Nothing in this module cuts a version automatically.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('workspaces/{workspace}/games/{game}')->group(function () {
        /*
         * Prototypes: the built things.
         */
        Route::prefix('prototypes')->group(function () {
            Route::get('/', [PrototypeController::class, 'index'])->name('prototypes.index');
            Route::post('/', [PrototypeController::class, 'store'])->name('prototypes.store');

            Route::prefix('{prototype}')->group(function () {
                Route::get('/', [PrototypeController::class, 'show'])->name('prototypes.show');
                Route::patch('/', [PrototypeController::class, 'update'])->name('prototypes.update');

                Route::post('archive', [PrototypeLifecycleController::class, 'archive'])->name('prototypes.archive');

                Route::post('versions', [PrototypeVersionController::class, 'store'])->name('prototypes.versions.store');

                Route::prefix('versions/{prototypeVersion}')->group(function () {
                    Route::get('/', [PrototypeVersionController::class, 'show'])->name('prototypes.versions.show');

                    Route::post('artifacts', [PrototypeArtifactController::class, 'store'])
                        ->name('prototypes.versions.artifacts.store');

                    /*
                     * The download is a GET through the application rather than a link to a disk.
                     * Artifacts are private, and a signed URL would be a credential outliving the
                     * check that issued it — so authorization and bytes travel together.
                     */
                    Route::get('artifacts/{artifact}', [PrototypeArtifactController::class, 'download'])
                        ->name('prototypes.versions.artifacts.download');

                    Route::delete('artifacts/{artifact}', [PrototypeArtifactController::class, 'destroy'])
                        ->name('prototypes.versions.artifacts.destroy');
                });
            });
        });

        /*
         * Iterations: the design work itself.
         */
        Route::prefix('iterations')->group(function () {
            Route::get('/', [IterationController::class, 'index'])->name('iterations.index');
            Route::post('/', [IterationController::class, 'store'])->name('iterations.store');

            Route::prefix('{iteration}')->group(function () {
                Route::get('/', [IterationController::class, 'show'])->name('iterations.show');
                Route::patch('/', [IterationController::class, 'update'])->name('iterations.update');

                Route::post('start', [IterationLifecycleController::class, 'start'])->name('iterations.start');
                Route::post('complete', [IterationLifecycleController::class, 'complete'])->name('iterations.complete');
                Route::post('cancel', [IterationLifecycleController::class, 'cancel'])->name('iterations.cancel');

                Route::post('changes', [IterationChangeController::class, 'store'])->name('iterations.changes.store');
                Route::patch('changes/{change}', [IterationChangeController::class, 'update'])->name('iterations.changes.update');
                Route::delete('changes/{change}', [IterationChangeController::class, 'destroy'])->name('iterations.changes.destroy');

                Route::post('experiments', [IterationExperimentController::class, 'store'])->name('iterations.experiments.store');
                Route::patch('experiments/{experiment}', [IterationExperimentController::class, 'update'])->name('iterations.experiments.update');
                Route::post('experiments/{experiment}/start', [IterationExperimentController::class, 'start'])->name('iterations.experiments.start');
                Route::post('experiments/{experiment}/complete', [IterationExperimentController::class, 'complete'])->name('iterations.experiments.complete');
                Route::post('experiments/{experiment}/cancel', [IterationExperimentController::class, 'cancel'])->name('iterations.experiments.cancel');

                Route::post('decisions', [IterationDecisionController::class, 'store'])->name('iterations.decisions.store');
                Route::patch('decisions/{decision}', [IterationDecisionController::class, 'update'])->name('iterations.decisions.update');
                Route::post('decisions/{decision}/accept', [IterationDecisionController::class, 'accept'])->name('iterations.decisions.accept');
                Route::post('decisions/{decision}/reject', [IterationDecisionController::class, 'reject'])->name('iterations.decisions.reject');
                Route::post('decisions/{decision}/defer', [IterationDecisionController::class, 'defer'])->name('iterations.decisions.defer');

                Route::post('decisions/{decision}/evidence', [IterationEvidenceController::class, 'store'])->name('iterations.decisions.evidence.store');

                Route::post('playtests', [IterationPlaytestController::class, 'store'])->name('iterations.playtests.store');
                Route::delete('playtests/{link}', [IterationPlaytestController::class, 'destroy'])->name('iterations.playtests.destroy');

                Route::post('game-version', [IterationGameVersionController::class, 'store'])->name('iterations.game-version.store');
            });
        });
    });
});
