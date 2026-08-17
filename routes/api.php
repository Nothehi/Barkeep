<?php

use Illuminate\Support\Facades\Route;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\ChecklistItemCompletionController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\CriterionEvaluationController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\FrameworkArchiveController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\FrameworkContentController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\FrameworkController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\FrameworkPublicationController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\FrameworkVersionController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\FrameworkVersionLifecycleController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\GameFrameworkController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\GameFrameworkLifecycleController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\GameFrameworkProgressController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\PracticeCompletionController;
use Modules\DesignFramework\Presentation\Http\Controllers\Api\PromptResponseController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameArchiveController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameDesignPhaseController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameStatusController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameVersionController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\FeedbackController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\ObservationController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\ParticipantController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\PlaytestCancellationController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\PlaytestCompletionController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\PlaytestController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\PlaytestSessionController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\PlaytestSummaryController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\SessionCancellationController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\SessionCompletionController;
use Modules\Playtesting\Presentation\Http\Controllers\Api\SessionStartController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceArchiveController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceInvitationController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceLeaveController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceMemberController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceMemberInvitationController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceOwnershipController;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| A first-party JSON surface over the same application commands the Inertia
| screens use. It is session authenticated rather than token authenticated:
| the only client today is this application's own front end, and giving it
| bearer tokens would mean minting a credential that outlives the session for
| no gain. See bootstrap/app.php for the stateful middleware this group runs.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index'])->name('api.workspaces.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('api.workspaces.store');

    Route::prefix('workspaces/{workspace}')->scopeBindings()->group(function () {
        Route::get('/', [WorkspaceController::class, 'show'])->name('api.workspaces.show');
        Route::patch('/', [WorkspaceController::class, 'update'])->name('api.workspaces.update');

        Route::post('archive', [WorkspaceArchiveController::class, 'store'])->name('api.workspaces.archive');
        Route::post('leave', [WorkspaceLeaveController::class, 'store'])->name('api.workspaces.leave');
        Route::post('ownership/transfer', [WorkspaceOwnershipController::class, 'store'])->name('api.workspaces.ownership.transfer');

        Route::get('members', [WorkspaceMemberController::class, 'index'])->name('api.workspaces.members.index');
        Route::get('members/invitations', [WorkspaceMemberInvitationController::class, 'index'])->name('api.workspaces.members.invitations.index');
        Route::post('members/invitations', [WorkspaceMemberInvitationController::class, 'store'])->name('api.workspaces.members.invitations.store');
        Route::patch('members/{member}', [WorkspaceMemberController::class, 'update'])->name('api.workspaces.members.update');
        Route::delete('members/{member}', [WorkspaceMemberController::class, 'destroy'])->name('api.workspaces.members.destroy');
    });

    /*
     * Games are nested under their workspace because a game address is only
     * unique inside one. `{game}` is resolved by GameDesign's own explicit
     * binding, which looks it up through the workspace in the URL — so a game
     * from elsewhere 404s at resolution rather than being caught later by a
     * policy. See routes/games.php for the same arrangement on the screens.
     */
    Route::prefix('workspaces/{workspace}/games')->group(function () {
        Route::get('/', [GameController::class, 'index'])->name('api.workspaces.games.index');
        Route::post('/', [GameController::class, 'store'])->name('api.workspaces.games.store');

        Route::prefix('{game}')->group(function () {
            Route::get('/', [GameController::class, 'show'])->name('api.workspaces.games.show');
            Route::patch('/', [GameController::class, 'update'])->name('api.workspaces.games.update');

            Route::post('status', [GameStatusController::class, 'store'])->name('api.workspaces.games.status');
            Route::post('design-phase', [GameDesignPhaseController::class, 'store'])->name('api.workspaces.games.design-phase');
            Route::post('archive', [GameArchiveController::class, 'store'])->name('api.workspaces.games.archive');

            Route::get('versions', [GameVersionController::class, 'index'])->name('api.workspaces.games.versions.index');
            Route::post('versions', [GameVersionController::class, 'store'])->name('api.workspaces.games.versions.store');
            Route::get('versions/{version}', [GameVersionController::class, 'show'])->name('api.workspaces.games.versions.show');

            /*
             * A game's framework is a singleton sub-resource rather than a
             * collection, because a game follows one methodology at a time —
             * see section 46. `GET` answers 404 when it follows none, which is
             * what lets a client tell "not adopted" from "adopted and empty".
             *
             * Note what the URLs below do *not* contain: a framework, or a
             * version. Neither is a choice the request gets to make. The
             * adoption supplies both, so a criterion id from another edition
             * fails to resolve rather than being caught by a policy — which is
             * section 19's historical integrity as a routing property.
             *
             * Unticking a practice or a checklist item is the same POST with
             * `completed=false`, not a DELETE. What gets removed is the
             * studio's own completion row; expressing that as a DELETE on the
             * practice would read as removing the methodology's content.
             */
            Route::prefix('framework')->group(function () {
                Route::get('/', [GameFrameworkController::class, 'show'])->name('api.workspaces.games.framework.show');
                Route::post('/', [GameFrameworkController::class, 'store'])->name('api.workspaces.games.framework.store');

                Route::get('progress', [GameFrameworkProgressController::class, 'show'])->name('api.workspaces.games.framework.progress');

                Route::post('pause', [GameFrameworkLifecycleController::class, 'pause'])->name('api.workspaces.games.framework.pause');
                Route::post('resume', [GameFrameworkLifecycleController::class, 'resume'])->name('api.workspaces.games.framework.resume');
                Route::post('complete', [GameFrameworkLifecycleController::class, 'complete'])->name('api.workspaces.games.framework.complete');

                Route::get('evaluations', [CriterionEvaluationController::class, 'index'])->name('api.workspaces.games.framework.evaluations.index');
                Route::post('criteria/{criterion}/evaluate', [CriterionEvaluationController::class, 'store'])->name('api.workspaces.games.framework.criteria.evaluate');

                Route::get('practice-completions', [PracticeCompletionController::class, 'index'])->name('api.workspaces.games.framework.practice-completions.index');
                Route::post('practices/{practice}/complete', [PracticeCompletionController::class, 'store'])->name('api.workspaces.games.framework.practices.complete');

                Route::get('checklists', [ChecklistItemCompletionController::class, 'index'])->name('api.workspaces.games.framework.checklists.index');
                Route::post('checklist-items/{item}/complete', [ChecklistItemCompletionController::class, 'store'])->name('api.workspaces.games.framework.checklist-items.complete');

                Route::get('prompt-responses', [PromptResponseController::class, 'index'])->name('api.workspaces.games.framework.prompt-responses.index');
                Route::post('prompts/{prompt}/respond', [PromptResponseController::class, 'store'])->name('api.workspaces.games.framework.prompts.respond');
            });

            /*
             * Playtests are nested the whole way down rather than exposed at a
             * shorter top-level address, because each segment is resolved
             * *through* the one before it by Playtesting's own bindings. A
             * session id from somebody else's playtest fails to resolve rather
             * than being caught later by a policy — which is what lets these
             * ids be opaque uuids in a URL without any of them being a
             * capability. See routes/playtests.php for the same arrangement on
             * the screens.
             */
            Route::prefix('playtests')->group(function () {
                Route::get('/', [PlaytestController::class, 'index'])->name('api.workspaces.games.playtests.index');
                Route::post('/', [PlaytestController::class, 'store'])->name('api.workspaces.games.playtests.store');

                Route::prefix('{playtest}')->group(function () {
                    Route::get('/', [PlaytestController::class, 'show'])->name('api.workspaces.games.playtests.show');
                    Route::patch('/', [PlaytestController::class, 'update'])->name('api.workspaces.games.playtests.update');

                    Route::get('summary', [PlaytestSummaryController::class, 'show'])->name('api.workspaces.games.playtests.summary');
                    Route::post('complete', [PlaytestCompletionController::class, 'store'])->name('api.workspaces.games.playtests.complete');
                    Route::post('cancel', [PlaytestCancellationController::class, 'store'])->name('api.workspaces.games.playtests.cancel');

                    Route::get('sessions', [PlaytestSessionController::class, 'index'])->name('api.workspaces.games.playtests.sessions.index');
                    Route::post('sessions', [PlaytestSessionController::class, 'store'])->name('api.workspaces.games.playtests.sessions.store');

                    Route::prefix('sessions/{session}')->group(function () {
                        Route::get('/', [PlaytestSessionController::class, 'show'])->name('api.workspaces.games.playtests.sessions.show');
                        Route::patch('/', [PlaytestSessionController::class, 'update'])->name('api.workspaces.games.playtests.sessions.update');

                        Route::post('start', [SessionStartController::class, 'store'])->name('api.workspaces.games.playtests.sessions.start');
                        Route::post('complete', [SessionCompletionController::class, 'store'])->name('api.workspaces.games.playtests.sessions.complete');
                        Route::post('cancel', [SessionCancellationController::class, 'store'])->name('api.workspaces.games.playtests.sessions.cancel');

                        Route::get('participants', [ParticipantController::class, 'index'])->name('api.workspaces.games.playtests.sessions.participants.index');
                        Route::post('participants', [ParticipantController::class, 'store'])->name('api.workspaces.games.playtests.sessions.participants.store');
                        Route::delete('participants/{participant}', [ParticipantController::class, 'destroy'])->name('api.workspaces.games.playtests.sessions.participants.destroy');

                        Route::get('observations', [ObservationController::class, 'index'])->name('api.workspaces.games.playtests.sessions.observations.index');
                        Route::post('observations', [ObservationController::class, 'store'])->name('api.workspaces.games.playtests.sessions.observations.store');
                        Route::patch('observations/{observation}', [ObservationController::class, 'update'])->name('api.workspaces.games.playtests.sessions.observations.update');
                        Route::delete('observations/{observation}', [ObservationController::class, 'destroy'])->name('api.workspaces.games.playtests.sessions.observations.destroy');

                        Route::get('feedback', [FeedbackController::class, 'index'])->name('api.workspaces.games.playtests.sessions.feedback.index');
                        Route::post('feedback', [FeedbackController::class, 'store'])->name('api.workspaces.games.playtests.sessions.feedback.store');
                        Route::patch('feedback/{feedback}', [FeedbackController::class, 'update'])->name('api.workspaces.games.playtests.sessions.feedback.update');
                        Route::delete('feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('api.workspaces.games.playtests.sessions.feedback.destroy');
                    });
                });
            });
        });
    });

    /*
     * Frameworks are the one first-class collection in the API that is not
     * nested under a workspace. A methodology is not a studio's document — it
     * is something Barkeep publishes and studios adopt — so there is no tenant
     * to scope its address to, and framework slugs are globally unique.
     *
     * Authorization here is a different shape from everywhere else in the API.
     * Every signed in account may read a published framework and its content;
     * only a framework administrator may write one, or see a draft. Until the
     * Administration context exists that set is a configuration list, read in
     * exactly one place — see `FrameworkAdministrators`.
     *
     * The content routes are reads only. Authoring happens on the builder
     * screens, which submit as Inertia visits so the server can redirect and
     * flash; exposing a second write surface for the same operations would mean
     * two clients to keep honest about published-version immutability.
     */
    Route::prefix('frameworks')->group(function () {
        Route::get('/', [FrameworkController::class, 'index'])->name('api.frameworks.index');
        Route::post('/', [FrameworkController::class, 'store'])->name('api.frameworks.store');

        Route::prefix('{framework}')->group(function () {
            Route::get('/', [FrameworkController::class, 'show'])->name('api.frameworks.show');
            Route::patch('/', [FrameworkController::class, 'update'])->name('api.frameworks.update');

            Route::post('publish', [FrameworkPublicationController::class, 'store'])->name('api.frameworks.publish');
            Route::post('archive', [FrameworkArchiveController::class, 'store'])->name('api.frameworks.archive');

            Route::get('versions', [FrameworkVersionController::class, 'index'])->name('api.frameworks.versions.index');
            Route::post('versions', [FrameworkVersionController::class, 'store'])->name('api.frameworks.versions.store');

            Route::prefix('versions/{version}')->group(function () {
                Route::get('/', [FrameworkVersionController::class, 'show'])->name('api.frameworks.versions.show');
                Route::patch('/', [FrameworkVersionController::class, 'update'])->name('api.frameworks.versions.update');

                Route::post('publish', [FrameworkVersionLifecycleController::class, 'publish'])->name('api.frameworks.versions.publish');
                Route::post('archive', [FrameworkVersionLifecycleController::class, 'archive'])->name('api.frameworks.versions.archive');

                Route::get('phases', [FrameworkContentController::class, 'phases'])->name('api.frameworks.versions.phases.index');
                Route::get('phases/{phase}', [FrameworkContentController::class, 'phase'])->name('api.frameworks.versions.phases.show');

                Route::get('principles', [FrameworkContentController::class, 'principles'])->name('api.frameworks.versions.principles.index');
                Route::get('criteria', [FrameworkContentController::class, 'criteria'])->name('api.frameworks.versions.criteria.index');
                Route::get('practices', [FrameworkContentController::class, 'practices'])->name('api.frameworks.versions.practices.index');
                Route::get('prompts', [FrameworkContentController::class, 'prompts'])->name('api.frameworks.versions.prompts.index');
                Route::get('checklists', [FrameworkContentController::class, 'checklists'])->name('api.frameworks.versions.checklists.index');
            });
        });
    });

    Route::post('workspace-invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept'])
        ->name('api.workspace-invitations.accept');

    Route::post('workspace-invitations/{invitation}/revoke', [WorkspaceInvitationController::class, 'revoke'])
        ->name('api.workspace-invitations.revoke');
});

/*
 * Readable without a session: the landing page has to be able to say which
 * workspace a link is for before asking anyone to sign in. The response is
 * limited to that.
 */
Route::get('workspace-invitations/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('api.workspace-invitations.show');
