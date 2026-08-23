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
use Modules\GameDesign\Presentation\Http\Controllers\Api\DesignRecordController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameArchiveController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameDesignPhaseController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameStatusController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\GameVersionController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\MechanicArchiveController;
use Modules\GameDesign\Presentation\Http\Controllers\Api\MechanicController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\ActionCostController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\ActionEffectController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\ActionRewardController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceAnalysisController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceAssumptionController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceComparisonController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceObservationController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceProfileController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceProfileLifecycleController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceScenarioController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceSnapshotController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\BalanceVariableController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\EconomyActionController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\ResourceController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\ResourceFlowController;
use Modules\GameEconomy\Presentation\Http\Controllers\Api\ScenarioVariableController;
use Modules\GameRules\Presentation\Http\Controllers\Api\ConditionGroupController as RuleConditionGroupController;
use Modules\GameRules\Presentation\Http\Controllers\Api\DefeatConditionController;
use Modules\GameRules\Presentation\Http\Controllers\Api\GameEndConditionController;
use Modules\GameRules\Presentation\Http\Controllers\Api\GamePhaseController;
use Modules\GameRules\Presentation\Http\Controllers\Api\GameRuleController;
use Modules\GameRules\Presentation\Http\Controllers\Api\MechanicController as RuleMechanicController;
use Modules\GameRules\Presentation\Http\Controllers\Api\PhaseTransitionController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleActionController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleAnalysisController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleConditionController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleEffectController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleReferenceController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleRequirementController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleSetController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleSetLifecycleController;
use Modules\GameRules\Presentation\Http\Controllers\Api\RuleTriggerController;
use Modules\GameRules\Presentation\Http\Controllers\Api\VictoryConditionController;
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
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\DecisionController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\DecisionEvidenceController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\DecisionLifecycleController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\DesignChangeController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\ExperimentController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\ExperimentLifecycleController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\IterationController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\IterationGameVersionController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\IterationLifecycleController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\IterationPlaytestController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\IterationSummaryController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\IterationTimelineController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\PrototypeArchiveController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\PrototypeArtifactController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\PrototypeController;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Api\PrototypeVersionController;
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

            /*
             * The design record: a singleton sub-resource, because a game has one
             * design. There is no POST — the record is created by the first
             * PATCH, since the design exists as soon as anything about it is
             * known and making a caller create a container first would be the
             * storage shape leaking into the API.
             */
            Route::get('design', [DesignRecordController::class, 'show'])->name('api.workspaces.games.design.show');
            Route::patch('design', [DesignRecordController::class, 'update'])->name('api.workspaces.games.design.update');

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

            /*
             * Prototypes and iterations: what the studio built, and what it did with it.
             *
             * Nested the whole way down for the same reason playtests are, and the reason bears
             * repeating because a flatter shape is genuinely tempting here — `/iterations/{id}` is a
             * shorter address than this one. Each segment is resolved *through* the one before it by
             * PrototypeIteration's own bindings, so a change id from somebody else's iteration fails
             * to resolve rather than being caught later by a policy. Reaching an iteration without
             * its game would mean looking the parent up from the child, which is the reverse-lookup
             * pattern that turns a guessed uuid into cross-workspace access.
             *
             * Prototype versions are addressed by number — `versions/3` — because that is what a
             * designer says, and a number is unique inside its prototype. Everything else is a uuid.
             *
             * There is no update or delete route for a prototype version, and the absence is the
             * immutability rule as a routing property: once a version has been iterated on it is
             * part of the design record, and the way forward is to cut the next one.
             *
             * Two shapes worth pointing at. Attaching a playtest carries the id in the body rather
             * than the URL, so no route here binds a Playtesting model; detaching addresses the
             * association instead. And `game-version` is section 48's deliberate seam — the
             * designer's explicit decision that the design has moved on, never a side effect of
             * completing a cycle.
             */
            Route::prefix('prototypes')->group(function () {
                Route::get('/', [PrototypeController::class, 'index'])->name('api.workspaces.games.prototypes.index');
                Route::post('/', [PrototypeController::class, 'store'])->name('api.workspaces.games.prototypes.store');

                Route::prefix('{prototype}')->group(function () {
                    Route::get('/', [PrototypeController::class, 'show'])->name('api.workspaces.games.prototypes.show');
                    Route::patch('/', [PrototypeController::class, 'update'])->name('api.workspaces.games.prototypes.update');

                    Route::post('archive', [PrototypeArchiveController::class, 'store'])->name('api.workspaces.games.prototypes.archive');

                    Route::get('versions', [PrototypeVersionController::class, 'index'])->name('api.workspaces.games.prototypes.versions.index');
                    Route::post('versions', [PrototypeVersionController::class, 'store'])->name('api.workspaces.games.prototypes.versions.store');

                    Route::prefix('versions/{prototypeVersion}')->group(function () {
                        Route::get('/', [PrototypeVersionController::class, 'show'])->name('api.workspaces.games.prototypes.versions.show');

                        Route::get('artifacts', [PrototypeArtifactController::class, 'index'])->name('api.workspaces.games.prototypes.versions.artifacts.index');
                        Route::post('artifacts', [PrototypeArtifactController::class, 'store'])->name('api.workspaces.games.prototypes.versions.artifacts.store');
                        Route::delete('artifacts/{artifact}', [PrototypeArtifactController::class, 'destroy'])->name('api.workspaces.games.prototypes.versions.artifacts.destroy');
                    });
                });
            });

            Route::prefix('iterations')->group(function () {
                Route::get('/', [IterationController::class, 'index'])->name('api.workspaces.games.iterations.index');
                Route::post('/', [IterationController::class, 'store'])->name('api.workspaces.games.iterations.store');

                Route::prefix('{iteration}')->group(function () {
                    Route::get('/', [IterationController::class, 'show'])->name('api.workspaces.games.iterations.show');
                    Route::patch('/', [IterationController::class, 'update'])->name('api.workspaces.games.iterations.update');

                    Route::get('summary', [IterationSummaryController::class, 'show'])->name('api.workspaces.games.iterations.summary');
                    Route::get('timeline', [IterationTimelineController::class, 'show'])->name('api.workspaces.games.iterations.timeline');

                    Route::post('start', [IterationLifecycleController::class, 'start'])->name('api.workspaces.games.iterations.start');
                    Route::post('complete', [IterationLifecycleController::class, 'complete'])->name('api.workspaces.games.iterations.complete');
                    Route::post('cancel', [IterationLifecycleController::class, 'cancel'])->name('api.workspaces.games.iterations.cancel');

                    Route::get('changes', [DesignChangeController::class, 'index'])->name('api.workspaces.games.iterations.changes.index');
                    Route::post('changes', [DesignChangeController::class, 'store'])->name('api.workspaces.games.iterations.changes.store');
                    Route::patch('changes/{change}', [DesignChangeController::class, 'update'])->name('api.workspaces.games.iterations.changes.update');
                    Route::delete('changes/{change}', [DesignChangeController::class, 'destroy'])->name('api.workspaces.games.iterations.changes.destroy');

                    Route::get('experiments', [ExperimentController::class, 'index'])->name('api.workspaces.games.iterations.experiments.index');
                    Route::post('experiments', [ExperimentController::class, 'store'])->name('api.workspaces.games.iterations.experiments.store');
                    Route::patch('experiments/{experiment}', [ExperimentController::class, 'update'])->name('api.workspaces.games.iterations.experiments.update');
                    Route::post('experiments/{experiment}/start', [ExperimentLifecycleController::class, 'start'])->name('api.workspaces.games.iterations.experiments.start');
                    Route::post('experiments/{experiment}/complete', [ExperimentLifecycleController::class, 'complete'])->name('api.workspaces.games.iterations.experiments.complete');
                    Route::post('experiments/{experiment}/cancel', [ExperimentLifecycleController::class, 'cancel'])->name('api.workspaces.games.iterations.experiments.cancel');

                    Route::get('decisions', [DecisionController::class, 'index'])->name('api.workspaces.games.iterations.decisions.index');
                    Route::post('decisions', [DecisionController::class, 'store'])->name('api.workspaces.games.iterations.decisions.store');
                    Route::patch('decisions/{decision}', [DecisionController::class, 'update'])->name('api.workspaces.games.iterations.decisions.update');
                    Route::post('decisions/{decision}/accept', [DecisionLifecycleController::class, 'accept'])->name('api.workspaces.games.iterations.decisions.accept');
                    Route::post('decisions/{decision}/reject', [DecisionLifecycleController::class, 'reject'])->name('api.workspaces.games.iterations.decisions.reject');
                    Route::post('decisions/{decision}/defer', [DecisionLifecycleController::class, 'defer'])->name('api.workspaces.games.iterations.decisions.defer');

                    Route::get('decisions/{decision}/evidence', [DecisionEvidenceController::class, 'index'])->name('api.workspaces.games.iterations.decisions.evidence.index');
                    Route::post('decisions/{decision}/evidence', [DecisionEvidenceController::class, 'store'])->name('api.workspaces.games.iterations.decisions.evidence.store');

                    Route::get('playtests', [IterationPlaytestController::class, 'index'])->name('api.workspaces.games.iterations.playtests.index');
                    Route::post('playtests', [IterationPlaytestController::class, 'store'])->name('api.workspaces.games.iterations.playtests.store');
                    Route::delete('playtests/{link}', [IterationPlaytestController::class, 'destroy'])->name('api.workspaces.games.iterations.playtests.destroy');

                    Route::post('game-version', [IterationGameVersionController::class, 'store'])->name('api.workspaces.games.iterations.game-version.store');
                });
            });

            /*
             * The balance configuration of one design state.
             *
             * Nested under `versions/{version}` rather than under the game, which is this module's
             * foundational decision rather than a routing preference: wood income was 2 in v1 and 3
             * in v2, and an address that named only the game would have no way to say which of
             * those it meant.
             *
             * `{version}` is GameDesign's own binding, reused. Every segment below it resolves
             * through the one before — a resource through its profile, a cost through its action, an
             * override through its scenario — so a variable id from somebody else's configuration
             * 404s at resolution rather than being caught later by a policy.
             *
             * Two shapes worth pointing at. `snapshots/compare` takes its pair as query parameters,
             * because a comparison is a question about two records rather than a record owning
             * another; both are still resolved through the profile in the controller. And
             * `analysis` answers both verbs on one address: GET reads the findings silently, POST
             * reads exactly the same findings and announces that somebody looked — neither writes
             * anything, and neither persists the result.
             */
            Route::prefix('versions/{version}/balance-profiles')->group(function () {
                Route::get('/', [BalanceProfileController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.index');
                Route::post('/', [BalanceProfileController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.store');

                Route::prefix('{profile}')->group(function () {
                    Route::get('/', [BalanceProfileController::class, 'show'])->name('api.workspaces.games.versions.balance-profiles.show');
                    Route::patch('/', [BalanceProfileController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.update');

                    Route::post('activate', [BalanceProfileLifecycleController::class, 'activate'])->name('api.workspaces.games.versions.balance-profiles.activate');
                    Route::post('archive', [BalanceProfileLifecycleController::class, 'archive'])->name('api.workspaces.games.versions.balance-profiles.archive');

                    Route::get('resources', [ResourceController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.resources.index');
                    Route::post('resources', [ResourceController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.resources.store');
                    Route::get('resources/{resourceType}', [ResourceController::class, 'show'])->name('api.workspaces.games.versions.balance-profiles.resources.show');
                    Route::patch('resources/{resourceType}', [ResourceController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.resources.update');
                    Route::delete('resources/{resourceType}', [ResourceController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.resources.destroy');

                    Route::get('flows', [ResourceFlowController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.flows.index');
                    Route::post('flows', [ResourceFlowController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.flows.store');
                    Route::patch('flows/{flow}', [ResourceFlowController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.flows.update');
                    Route::delete('flows/{flow}', [ResourceFlowController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.flows.destroy');

                    Route::get('actions', [EconomyActionController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.actions.index');
                    Route::post('actions', [EconomyActionController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.actions.store');

                    Route::prefix('actions/{economyAction}')->group(function () {
                        Route::get('/', [EconomyActionController::class, 'show'])->name('api.workspaces.games.versions.balance-profiles.actions.show');
                        Route::patch('/', [EconomyActionController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.actions.update');
                        Route::delete('/', [EconomyActionController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.actions.destroy');

                        Route::get('costs', [ActionCostController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.actions.costs.index');
                        Route::post('costs', [ActionCostController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.actions.costs.store');
                        Route::patch('costs/{cost}', [ActionCostController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.actions.costs.update');
                        Route::delete('costs/{cost}', [ActionCostController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.actions.costs.destroy');

                        Route::get('rewards', [ActionRewardController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.actions.rewards.index');
                        Route::post('rewards', [ActionRewardController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.actions.rewards.store');
                        Route::patch('rewards/{reward}', [ActionRewardController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.actions.rewards.update');
                        Route::delete('rewards/{reward}', [ActionRewardController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.actions.rewards.destroy');

                        Route::get('effects', [ActionEffectController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.actions.effects.index');
                        Route::post('effects', [ActionEffectController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.actions.effects.store');
                        Route::patch('effects/{effect}', [ActionEffectController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.actions.effects.update');
                        Route::delete('effects/{effect}', [ActionEffectController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.actions.effects.destroy');
                    });

                    Route::get('variables', [BalanceVariableController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.variables.index');
                    Route::post('variables', [BalanceVariableController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.variables.store');
                    Route::patch('variables/{variable}', [BalanceVariableController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.variables.update');
                    Route::delete('variables/{variable}', [BalanceVariableController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.variables.destroy');

                    Route::get('scenarios', [BalanceScenarioController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.scenarios.index');
                    Route::post('scenarios', [BalanceScenarioController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.scenarios.store');

                    Route::prefix('scenarios/{scenario}')->group(function () {
                        Route::get('/', [BalanceScenarioController::class, 'show'])->name('api.workspaces.games.versions.balance-profiles.scenarios.show');
                        Route::patch('/', [BalanceScenarioController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.scenarios.update');
                        Route::post('archive', [BalanceScenarioController::class, 'archive'])->name('api.workspaces.games.versions.balance-profiles.scenarios.archive');

                        Route::get('variables', [ScenarioVariableController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.scenarios.variables.index');
                        Route::post('variables', [ScenarioVariableController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.scenarios.variables.store');
                        Route::delete('variables/{override}', [ScenarioVariableController::class, 'destroy'])->name('api.workspaces.games.versions.balance-profiles.scenarios.variables.destroy');
                    });

                    Route::get('assumptions', [BalanceAssumptionController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.assumptions.index');
                    Route::post('assumptions', [BalanceAssumptionController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.assumptions.store');
                    Route::patch('assumptions/{assumption}', [BalanceAssumptionController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.assumptions.update');

                    Route::get('observations', [BalanceObservationController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.observations.index');
                    Route::post('observations', [BalanceObservationController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.observations.store');
                    Route::patch('observations/{balanceObservation}', [BalanceObservationController::class, 'update'])->name('api.workspaces.games.versions.balance-profiles.observations.update');

                    Route::get('analysis', [BalanceAnalysisController::class, 'show'])->name('api.workspaces.games.versions.balance-profiles.analysis.show');
                    Route::post('analysis', [BalanceAnalysisController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.analysis.store');

                    Route::get('snapshots', [BalanceSnapshotController::class, 'index'])->name('api.workspaces.games.versions.balance-profiles.snapshots.index');
                    Route::post('snapshots', [BalanceSnapshotController::class, 'store'])->name('api.workspaces.games.versions.balance-profiles.snapshots.store');
                    Route::get('snapshots/compare', [BalanceComparisonController::class, 'show'])->name('api.workspaces.games.versions.balance-profiles.snapshots.compare');
                });
            });

            /*
             * The rule system of one design state.
             *
             * Nested under `versions/{version}` for the same reason the balance profile is, and it
             * is the same reason: combat was resolved with one die in v1 and two in v2, and an
             * address that named only the game would have no way to say which of those it meant.
             *
             * Section 40 of the module brief sketches flat addresses — `/rule-sets/{id}`,
             * `/game-phases/{id}`. They are deliberately not built that way. Reaching a rule set
             * without its version would mean looking the parent up *from* the child, which is the
             * reverse-lookup pattern that turns a guessed id into cross-workspace access; every
             * other module in this file made the same call. Each segment below resolves through the
             * one before it, so a phase id from somebody else's rule system 404s at resolution
             * rather than being caught later by a policy.
             *
             * `{version}` is GameDesign's binding, reused. Seven names here are longer than they
             * would naturally be — `{gameRule}`, `{gamePhase}`, `{ruleMechanic}`, `{ruleAction}`,
             * `{ruleEffect}`, `{ruleCondition}` — because the short forms are already claimed by
             * GameDesign, DesignFramework and GameEconomy. See `.ai/rules/providers.md`.
             *
             * `clone` answers 201 with the new draft: it is what an active rule set offers instead
             * of editing, so the caller almost always wants to work on the copy.
             */
            Route::prefix('versions/{version}/rule-sets')->group(function () {
                Route::get('/', [RuleSetController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.index');
                Route::post('/', [RuleSetController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.store');

                Route::prefix('{ruleSet}')->group(function () {
                    Route::get('/', [RuleSetController::class, 'show'])->name('api.workspaces.games.versions.rule-sets.show');
                    Route::patch('/', [RuleSetController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.update');

                    Route::post('activate', [RuleSetLifecycleController::class, 'activate'])->name('api.workspaces.games.versions.rule-sets.activate');
                    Route::post('archive', [RuleSetLifecycleController::class, 'archive'])->name('api.workspaces.games.versions.rule-sets.archive');
                    Route::post('clone', [RuleSetLifecycleController::class, 'clone'])->name('api.workspaces.games.versions.rule-sets.clone');

                    Route::get('rules', [GameRuleController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.rules.index');
                    Route::post('rules', [GameRuleController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.rules.store');
                    Route::post('rules/order', [GameRuleController::class, 'reorder'])->name('api.workspaces.games.versions.rule-sets.rules.order');

                    Route::prefix('rules/{gameRule}')->group(function () {
                        Route::get('/', [GameRuleController::class, 'show'])->name('api.workspaces.games.versions.rule-sets.rules.show');
                        Route::patch('/', [GameRuleController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.rules.update');
                        Route::delete('/', [GameRuleController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.rules.destroy');

                        Route::post('references', [RuleReferenceController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.rules.references.store');
                        Route::delete('references/{reference}', [RuleReferenceController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.rules.references.destroy');
                    });

                    Route::get('references', [RuleReferenceController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.references.index');

                    Route::get('mechanics', [RuleMechanicController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.mechanics.index');
                    Route::post('mechanics', [RuleMechanicController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.mechanics.store');
                    Route::patch('mechanics/{ruleMechanic}', [RuleMechanicController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.mechanics.update');
                    Route::delete('mechanics/{ruleMechanic}', [RuleMechanicController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.mechanics.destroy');

                    Route::get('phases', [GamePhaseController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.phases.index');
                    Route::post('phases', [GamePhaseController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.phases.store');
                    Route::patch('phases/{gamePhase}', [GamePhaseController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.phases.update');
                    Route::delete('phases/{gamePhase}', [GamePhaseController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.phases.destroy');

                    Route::get('transitions', [PhaseTransitionController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.transitions.index');
                    Route::post('transitions', [PhaseTransitionController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.transitions.store');
                    Route::patch('transitions/{transition}', [PhaseTransitionController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.transitions.update');
                    Route::delete('transitions/{transition}', [PhaseTransitionController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.transitions.destroy');

                    Route::get('actions', [RuleActionController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.actions.index');
                    Route::post('actions', [RuleActionController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.actions.store');
                    Route::post('actions/order', [RuleActionController::class, 'reorder'])->name('api.workspaces.games.versions.rule-sets.actions.order');
                    Route::get('actions/{ruleAction}', [RuleActionController::class, 'show'])->name('api.workspaces.games.versions.rule-sets.actions.show');
                    Route::patch('actions/{ruleAction}', [RuleActionController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.actions.update');
                    Route::delete('actions/{ruleAction}', [RuleActionController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.actions.destroy');

                    Route::get('requirements', [RuleRequirementController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.requirements.index');
                    Route::post('requirements', [RuleRequirementController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.requirements.store');
                    Route::patch('requirements/{requirement}', [RuleRequirementController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.requirements.update');
                    Route::delete('requirements/{requirement}', [RuleRequirementController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.requirements.destroy');

                    Route::get('effects', [RuleEffectController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.effects.index');
                    Route::post('effects', [RuleEffectController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.effects.store');
                    Route::patch('effects/{ruleEffect}', [RuleEffectController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.effects.update');
                    Route::delete('effects/{ruleEffect}', [RuleEffectController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.effects.destroy');

                    Route::get('conditions', [RuleConditionController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.conditions.index');
                    Route::post('conditions', [RuleConditionController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.conditions.store');
                    Route::patch('conditions/{ruleCondition}', [RuleConditionController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.conditions.update');
                    Route::delete('conditions/{ruleCondition}', [RuleConditionController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.conditions.destroy');

                    Route::get('condition-groups', [RuleConditionGroupController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.condition-groups.index');
                    Route::post('condition-groups', [RuleConditionGroupController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.condition-groups.store');

                    Route::prefix('condition-groups/{conditionGroup}')->group(function () {
                        Route::patch('/', [RuleConditionGroupController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.condition-groups.update');
                        Route::delete('/', [RuleConditionGroupController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.condition-groups.destroy');

                        Route::post('conditions', [RuleConditionGroupController::class, 'storeCondition'])->name('api.workspaces.games.versions.rule-sets.condition-groups.conditions.store');
                        Route::delete('conditions/{membership}', [RuleConditionGroupController::class, 'destroyCondition'])->name('api.workspaces.games.versions.rule-sets.condition-groups.conditions.destroy');
                    });

                    Route::get('triggers', [RuleTriggerController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.triggers.index');
                    Route::post('triggers', [RuleTriggerController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.triggers.store');
                    Route::patch('triggers/{trigger}', [RuleTriggerController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.triggers.update');
                    Route::delete('triggers/{trigger}', [RuleTriggerController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.triggers.destroy');

                    Route::get('victory-conditions', [VictoryConditionController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.victory-conditions.index');
                    Route::post('victory-conditions', [VictoryConditionController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.victory-conditions.store');
                    Route::patch('victory-conditions/{victoryCondition}', [VictoryConditionController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.victory-conditions.update');
                    Route::delete('victory-conditions/{victoryCondition}', [VictoryConditionController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.victory-conditions.destroy');

                    Route::get('defeat-conditions', [DefeatConditionController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.defeat-conditions.index');
                    Route::post('defeat-conditions', [DefeatConditionController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.defeat-conditions.store');
                    Route::patch('defeat-conditions/{defeatCondition}', [DefeatConditionController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.defeat-conditions.update');
                    Route::delete('defeat-conditions/{defeatCondition}', [DefeatConditionController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.defeat-conditions.destroy');

                    Route::get('end-conditions', [GameEndConditionController::class, 'index'])->name('api.workspaces.games.versions.rule-sets.end-conditions.index');
                    Route::post('end-conditions', [GameEndConditionController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.end-conditions.store');
                    Route::patch('end-conditions/{endCondition}', [GameEndConditionController::class, 'update'])->name('api.workspaces.games.versions.rule-sets.end-conditions.update');
                    Route::delete('end-conditions/{endCondition}', [GameEndConditionController::class, 'destroy'])->name('api.workspaces.games.versions.rule-sets.end-conditions.destroy');

                    Route::get('analysis', [RuleAnalysisController::class, 'show'])->name('api.workspaces.games.versions.rule-sets.analysis.show');
                    Route::post('analysis', [RuleAnalysisController::class, 'store'])->name('api.workspaces.games.versions.rule-sets.analysis.store');
                    Route::get('graph', [RuleAnalysisController::class, 'graph'])->name('api.workspaces.games.versions.rule-sets.graph');
                    Route::post('validate', [RuleAnalysisController::class, 'validateRuleSet'])->name('api.workspaces.games.versions.rule-sets.validate');
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

    /*
     * The design vocabulary.
     *
     * Not nested under a workspace, and the only collection here that is not.
     * A mechanic belongs to the platform rather than to a studio, so there is
     * nothing to scope it by — and the vocabulary is only worth having because
     * every game picks from the same one.
     */
    Route::prefix('mechanics')->group(function () {
        Route::get('/', [MechanicController::class, 'index'])->name('api.mechanics.index');
        Route::post('/', [MechanicController::class, 'store'])->name('api.mechanics.store');

        Route::prefix('{mechanic}')->group(function () {
            Route::get('/', [MechanicController::class, 'show'])->name('api.mechanics.show');
            Route::patch('/', [MechanicController::class, 'update'])->name('api.mechanics.update');

            Route::post('archive', [MechanicArchiveController::class, 'store'])->name('api.mechanics.archive');
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
