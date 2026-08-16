<?php

use Illuminate\Support\Facades\Route;
use Modules\GameDesign\Presentation\Http\Controllers\Web\GameController;
use Modules\GameDesign\Presentation\Http\Controllers\Web\GameLifecycleController;
use Modules\GameDesign\Presentation\Http\Controllers\Web\GameSettingsController;
use Modules\GameDesign\Presentation\Http\Controllers\Web\GameVersionController;

/*
|--------------------------------------------------------------------------
| Game screens
|--------------------------------------------------------------------------
|
| Games live inside a workspace and are addressed by a slug that is only
| unique within it, so every URL here carries both:
|
|     /app/workspaces/prototype-lab/games/bears-and-bridges
|
| The workspace segment is not decoration. `{game}` is resolved by an explicit
| binding registered in GameDesignServiceProvider, which looks the game up
| *through* the workspace in the URL — so a game belonging to a different
| workspace never resolves, and the request 404s before a handler runs. That
| is why there is no `scopeBindings()` call here: the scoping is in the
| binding itself rather than inferred from a relation, which is what keeps
| Workspace from having to know GameDesign exists.
|
| Lifecycle changes are POSTs to named actions rather than a PATCH of the
| status field, because they are actions with rules rather than an editable
| attribute. Archival has its own route for the same reason, and because it
| cannot be undone.
|
| There is no `games/create` route: creating a game is a dialog on the list
| screen. "create" is a reserved game address anyway, so adding one later
| would not collide.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('workspaces/{workspace}/games')->group(function () {
        Route::get('/', [GameController::class, 'index'])->name('games.index');
        Route::post('/', [GameController::class, 'store'])->name('games.store');

        Route::prefix('{game}')->group(function () {
            Route::get('/', [GameController::class, 'show'])->name('games.show');

            Route::get('settings', [GameSettingsController::class, 'edit'])->name('games.settings.edit');
            Route::patch('/', [GameSettingsController::class, 'update'])->name('games.update');
            Route::post('archive', [GameSettingsController::class, 'archive'])->name('games.archive');

            Route::post('status', [GameLifecycleController::class, 'changeStatus'])->name('games.status');
            Route::post('design-phase', [GameLifecycleController::class, 'changeDesignPhase'])->name('games.design-phase');

            Route::get('versions', [GameVersionController::class, 'index'])->name('games.versions.index');
            Route::post('versions', [GameVersionController::class, 'store'])->name('games.versions.store');
            Route::get('versions/{version}', [GameVersionController::class, 'show'])->name('games.versions.show');
        });
    });
});
