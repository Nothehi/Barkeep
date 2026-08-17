<?php

use Illuminate\Support\Facades\Route;
use Modules\GameDesign\Presentation\Http\Controllers\Web\MechanicArchiveController;
use Modules\GameDesign\Presentation\Http\Controllers\Web\MechanicController;

/*
|--------------------------------------------------------------------------
| The design vocabulary
|--------------------------------------------------------------------------
|
| Not nested under a workspace, and that is the point:
|
|     /app/mechanics/worker-placement
|
| A mechanic is the platform's rather than a studio's. Every game picks from
| this one list, which is the only reason the list is worth having — two games
| that both use worker placement have to say so with the same word or nothing
| can ever be compared across them.
|
| So there is no workspace segment to resolve through, and no scoping to do.
| Reading is open to every signed in account; writing requires being named in
| `game-design.curators`, which `MechanicPolicy` is the only thing that asks.
|
| Retiring is a POST to a named action rather than a DELETE, because nothing is
| deleted: the term stops being offered and the games that already claimed it
| keep saying what they said.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('mechanics')->group(function () {
        Route::get('/', [MechanicController::class, 'index'])->name('mechanics.index');
        Route::post('/', [MechanicController::class, 'store'])->name('mechanics.store');

        Route::patch('{mechanic}', [MechanicController::class, 'update'])->name('mechanics.update');

        Route::post('{mechanic}/archive', [MechanicArchiveController::class, 'store'])
            ->name('mechanics.archive');
    });
});
