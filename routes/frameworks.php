<?php

use Illuminate\Support\Facades\Route;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\ChecklistController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\ChecklistItemController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\CriterionController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\FrameworkController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\FrameworkLifecycleController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\FrameworkVersionController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\FrameworkVersionLifecycleController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\PhaseController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\PracticeController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\PrincipleController;
use Modules\DesignFramework\Presentation\Http\Controllers\Web\PromptController;

/*
|--------------------------------------------------------------------------
| Framework administration screens
|--------------------------------------------------------------------------
|
| The only screens in the application that are not nested under a workspace:
|
|     /app/frameworks/board-game-design/versions/1/phases/core-loop
|
| That is the interface telling the truth about the domain. A methodology is
| not a studio's document — it is something Barkeep publishes and studios
| adopt — so there is no tenant to scope its address to and framework slugs
| are globally unique.
|
| What takes the place of tenancy here is a different kind of authorization
| entirely. Every signed in account may read a published framework; only a
| framework administrator may write one, and only they can see a draft. Until
| the Administration context exists that set is a configuration list — see
| `FrameworkAdministrators` — and it is read in exactly one place.
|
| Each segment is resolved *through* the one before it by explicit bindings
| registered in DesignFrameworkServiceProvider: a version through its
| framework, a phase through its version, content through its version, and a
| checklist item through its checklist. So a version number belonging to
| another framework does not 403; it fails to resolve, and the request 404s
| before a handler runs.
|
| Content is addressed by uuid rather than by slug, and only phases are not.
| A phase is a place a designer navigates to and links people to; a criterion
| is edited inside a builder that already knows which one it is holding.
|
| Reorders are POSTs to a named action rather than a PATCH of a position
| field. A position is not an attribute a client sets: `ContentSequencer`
| allocates it and rewrites the whole sibling set, which is what keeps the
| ordering contiguous no matter what state it was in beforehand.
|
| Publishing a version is the most consequential route in the file. It ends
| that version's editable life — every route below it starts refusing — and
| opens it to adoption. There is no route back, because there is no
| transition back: see `FrameworkStatus`.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('frameworks')->group(function () {
        Route::get('/', [FrameworkController::class, 'index'])->name('frameworks.index');
        Route::post('/', [FrameworkController::class, 'store'])->name('frameworks.store');

        Route::prefix('{framework}')->group(function () {
            Route::get('/', [FrameworkController::class, 'show'])->name('frameworks.show');
            Route::patch('/', [FrameworkController::class, 'update'])->name('frameworks.update');

            Route::post('publish', [FrameworkLifecycleController::class, 'publish'])->name('frameworks.publish');
            Route::post('archive', [FrameworkLifecycleController::class, 'archive'])->name('frameworks.archive');

            Route::post('versions', [FrameworkVersionController::class, 'store'])->name('frameworks.versions.store');

            Route::prefix('versions/{version}')->group(function () {
                Route::get('/', [FrameworkVersionController::class, 'show'])->name('frameworks.versions.show');
                Route::patch('/', [FrameworkVersionController::class, 'update'])->name('frameworks.versions.update');

                Route::post('publish', [FrameworkVersionLifecycleController::class, 'publish'])
                    ->name('frameworks.versions.publish');
                Route::post('archive', [FrameworkVersionLifecycleController::class, 'archive'])
                    ->name('frameworks.versions.archive');

                Route::post('phases', [PhaseController::class, 'store'])->name('frameworks.versions.phases.store');
                Route::patch('phases/{phase}', [PhaseController::class, 'update'])
                    ->name('frameworks.versions.phases.update');
                Route::post('phases/{phase}/reorder', [PhaseController::class, 'reorder'])
                    ->name('frameworks.versions.phases.reorder');

                Route::post('principles', [PrincipleController::class, 'store'])
                    ->name('frameworks.versions.principles.store');
                Route::patch('principles/{principle}', [PrincipleController::class, 'update'])
                    ->name('frameworks.versions.principles.update');
                Route::post('principles/{principle}/reorder', [PrincipleController::class, 'reorder'])
                    ->name('frameworks.versions.principles.reorder');

                Route::post('criteria', [CriterionController::class, 'store'])
                    ->name('frameworks.versions.criteria.store');
                Route::patch('criteria/{criterion}', [CriterionController::class, 'update'])
                    ->name('frameworks.versions.criteria.update');
                Route::post('criteria/{criterion}/reorder', [CriterionController::class, 'reorder'])
                    ->name('frameworks.versions.criteria.reorder');

                Route::post('practices', [PracticeController::class, 'store'])
                    ->name('frameworks.versions.practices.store');
                Route::patch('practices/{practice}', [PracticeController::class, 'update'])
                    ->name('frameworks.versions.practices.update');
                Route::post('practices/{practice}/reorder', [PracticeController::class, 'reorder'])
                    ->name('frameworks.versions.practices.reorder');

                Route::post('prompts', [PromptController::class, 'store'])
                    ->name('frameworks.versions.prompts.store');
                Route::patch('prompts/{prompt}', [PromptController::class, 'update'])
                    ->name('frameworks.versions.prompts.update');
                Route::post('prompts/{prompt}/reorder', [PromptController::class, 'reorder'])
                    ->name('frameworks.versions.prompts.reorder');

                Route::post('checklists', [ChecklistController::class, 'store'])
                    ->name('frameworks.versions.checklists.store');

                Route::prefix('checklists/{checklist}')->group(function () {
                    Route::patch('/', [ChecklistController::class, 'update'])
                        ->name('frameworks.versions.checklists.update');
                    Route::post('reorder', [ChecklistController::class, 'reorder'])
                        ->name('frameworks.versions.checklists.reorder');

                    Route::post('items', [ChecklistItemController::class, 'store'])
                        ->name('frameworks.versions.checklists.items.store');
                    Route::patch('items/{item}', [ChecklistItemController::class, 'update'])
                        ->name('frameworks.versions.checklists.items.update');
                    Route::post('items/{item}/reorder', [ChecklistItemController::class, 'reorder'])
                        ->name('frameworks.versions.checklists.items.reorder');
                });
            });
        });
    });
});
