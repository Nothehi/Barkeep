<?php

use Illuminate\Support\Facades\Route;
use Modules\Playtesting\Presentation\Http\Controllers\Web\PlaytestController;
use Modules\Playtesting\Presentation\Http\Controllers\Web\PlaytestFeedbackController;
use Modules\Playtesting\Presentation\Http\Controllers\Web\PlaytestLifecycleController;
use Modules\Playtesting\Presentation\Http\Controllers\Web\PlaytestObservationController;
use Modules\Playtesting\Presentation\Http\Controllers\Web\PlaytestParticipantController;
use Modules\Playtesting\Presentation\Http\Controllers\Web\PlaytestSessionController;
use Modules\Playtesting\Presentation\Http\Controllers\Web\PlaytestSessionLifecycleController;

/*
|--------------------------------------------------------------------------
| Playtest screens
|--------------------------------------------------------------------------
|
| Playtests live inside a game, which lives inside a workspace, and every URL
| here carries the whole chain:
|
|     /app/workspaces/prototype-lab/games/bears-and-bridges/playtests/{id}
|
| The nesting is not decoration and it is not REST orthodoxy either. Each
| segment is resolved *through* the one before it by explicit bindings
| registered in PlaytestingServiceProvider — a playtest through its game, a
| session through its playtest, a participant or observation through its
| session. So a session id belonging to somebody else's playtest does not 403;
| it fails to resolve, and the request 404s before a handler runs.
|
| That is what lets these ids be opaque uuids in a URL without any of them
| being a capability. It is also why the sessions are not exposed at
| /sessions/{session} as a shorter top-level address would suggest: reaching a
| session without its playtest would mean looking the parent up *from* the
| child, which is exactly the reverse-lookup pattern that turns a guessed id
| into cross-workspace access.
|
| Playtests are addressed by uuid rather than by a slug. A game has an address
| somebody types and shares; a playtest is reached from the game's own screen,
| and a title like "Test whether the first-player advantage is too strong" does
| not want to be a URL segment.
|
| Lifecycle changes are POSTs to named actions rather than a PATCH of the
| status field, because they are actions with rules — completing a playtest
| requires that something actually happened — rather than an editable
| attribute.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('workspaces/{workspace}/games/{game}/playtests')->group(function () {
        Route::get('/', [PlaytestController::class, 'index'])->name('playtests.index');
        Route::post('/', [PlaytestController::class, 'store'])->name('playtests.store');

        Route::prefix('{playtest}')->group(function () {
            Route::get('/', [PlaytestController::class, 'show'])->name('playtests.show');
            Route::patch('/', [PlaytestController::class, 'update'])->name('playtests.update');

            Route::post('complete', [PlaytestLifecycleController::class, 'complete'])->name('playtests.complete');
            Route::post('cancel', [PlaytestLifecycleController::class, 'cancel'])->name('playtests.cancel');

            Route::post('sessions', [PlaytestSessionController::class, 'store'])->name('playtests.sessions.store');

            Route::prefix('sessions/{session}')->group(function () {
                Route::get('/', [PlaytestSessionController::class, 'show'])->name('playtests.sessions.show');
                Route::patch('/', [PlaytestSessionController::class, 'update'])->name('playtests.sessions.update');

                Route::post('start', [PlaytestSessionLifecycleController::class, 'start'])->name('playtests.sessions.start');
                Route::post('complete', [PlaytestSessionLifecycleController::class, 'complete'])->name('playtests.sessions.complete');
                Route::post('cancel', [PlaytestSessionLifecycleController::class, 'cancel'])->name('playtests.sessions.cancel');

                Route::post('participants', [PlaytestParticipantController::class, 'store'])->name('playtests.sessions.participants.store');
                Route::delete('participants/{participant}', [PlaytestParticipantController::class, 'destroy'])->name('playtests.sessions.participants.destroy');

                Route::post('observations', [PlaytestObservationController::class, 'store'])->name('playtests.sessions.observations.store');
                Route::patch('observations/{observation}', [PlaytestObservationController::class, 'update'])->name('playtests.sessions.observations.update');
                Route::delete('observations/{observation}', [PlaytestObservationController::class, 'destroy'])->name('playtests.sessions.observations.destroy');

                Route::post('feedback', [PlaytestFeedbackController::class, 'store'])->name('playtests.sessions.feedback.store');
                Route::patch('feedback/{feedback}', [PlaytestFeedbackController::class, 'update'])->name('playtests.sessions.feedback.update');
                Route::delete('feedback/{feedback}', [PlaytestFeedbackController::class, 'destroy'])->name('playtests.sessions.feedback.destroy');
            });
        });
    });
});
