<?php

use Illuminate\Support\Facades\Route;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\ChecklistItemCompletionController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\CriterionEvaluationController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\GameFrameworkController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\GameFrameworkLifecycleController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\GameFrameworkPhaseController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\PracticeCompletionController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\PromptResponseController;

/*
|--------------------------------------------------------------------------
| A game's framework screens
|--------------------------------------------------------------------------
|
| Where a designer actually works:
|
|     /app/workspaces/prototype-lab/games/bears-and-bridges/framework/phases/core-loop
|
| Note what is missing from that URL: the framework, and its version. Neither
| is named, because neither is a choice the request gets to make. The game
| adopted one edition and the whole screen is resolved through it — which is
| section 19's historical integrity turned into a routing property. A game on
| v1 addressing `core-loop` reaches v1's core loop phase for as long as the
| game exists, whatever v2 renamed or split, and there is no id it could
| substitute to reach a different edition.
|
| The chain is workspace → game → adoption → content. The first two segments
| are resolved by GameDesign's own binding, so a game in another workspace
| 404s before anything here runs; the adoption is found through the game; and
| a criterion, practice, checklist item or prompt is found through the
| adoption. Framework content ids are not secrets — a criterion belongs to a
| globally published edition — and they do not need to be: an id from v2
| simply does not resolve for a game on v1.
|
| One route carries an identifier in its body rather than its URL: adopting a
| framework. That one is unavoidable — a game choosing a methodology is
| choosing from every published edition on the platform, so there is no parent
| segment to resolve the choice through — and it is why
| `AssignFrameworkToGame` proves the version is published and adoptable itself.
|
| Recording work is a POST to a named action rather than a PATCH of a field,
| for the usual reason: evaluating a criterion and ticking a checklist item are
| actions with rules — the game must be open and the adoption active — rather
| than editable attributes.
|
| Unticking is the same route with `completed=false`, not a DELETE. The thing
| being toggled is the framework's requirement, which is not the caller's to
| delete; what gets removed is the studio's own completion row, and expressing
| that as a DELETE on a criterion or an item would read as removing the
| methodology's content.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('workspaces/{workspace}/games/{game}/framework')->group(function () {
        Route::get('/', [GameFrameworkController::class, 'show'])->name('games.framework.show');
        Route::post('/', [GameFrameworkController::class, 'store'])->name('games.framework.store');

        Route::post('pause', [GameFrameworkLifecycleController::class, 'pause'])->name('games.framework.pause');
        Route::post('resume', [GameFrameworkLifecycleController::class, 'resume'])->name('games.framework.resume');
        Route::post('complete', [GameFrameworkLifecycleController::class, 'complete'])->name('games.framework.complete');

        Route::get('phases/{phase}', [GameFrameworkPhaseController::class, 'show'])->name('games.framework.phases.show');

        Route::post('criteria/{criterion}/evaluate', [CriterionEvaluationController::class, 'store'])
            ->name('games.framework.criteria.evaluate');

        Route::post('practices/{practice}/complete', [PracticeCompletionController::class, 'store'])
            ->name('games.framework.practices.complete');

        Route::post('checklist-items/{item}/complete', [ChecklistItemCompletionController::class, 'store'])
            ->name('games.framework.checklist-items.complete');

        Route::post('prompts/{prompt}/respond', [PromptResponseController::class, 'store'])
            ->name('games.framework.prompts.respond');
    });
});
