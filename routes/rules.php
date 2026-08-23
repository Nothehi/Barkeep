<?php

use Illuminate\Support\Facades\Route;
use Modules\GameRules\Presentation\Http\Controllers\Web\ConditionGroupController;
use Modules\GameRules\Presentation\Http\Controllers\Web\GamePhaseController;
use Modules\GameRules\Presentation\Http\Controllers\Web\GameRuleController;
use Modules\GameRules\Presentation\Http\Controllers\Web\OutcomeController;
use Modules\GameRules\Presentation\Http\Controllers\Web\PhaseDesignerController;
use Modules\GameRules\Presentation\Http\Controllers\Web\PhaseTransitionController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleActionController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleAnalysisController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleBuilderController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleConditionController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleEffectController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleGraphController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleMechanicController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleReferenceController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleRequirementController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleSetController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleSetLifecycleController;
use Modules\GameRules\Presentation\Http\Controllers\Web\RuleTriggerController;

/*
|--------------------------------------------------------------------------
| Rules screens
|--------------------------------------------------------------------------
|
| A rule system belongs to a *design version*, not to a game, so every URL here carries the whole
| chain:
|
|     /app/workspaces/prototype-lab/games/bears-and-bridges/versions/4/rules/{ruleSet}/…
|
| That version segment is the module's foundational decision rather than a routing detail. Combat
| was resolved with one die in v1 and two in v2, and a URL that named only the game would have no
| way to say which of those it meant — which is exactly the ambiguity that makes historical
| playtests uninterpretable.
|
| The nesting is not decoration and it is not REST orthodoxy either. Each segment is resolved
| *through* the one before it by explicit bindings registered in GameRulesServiceProvider — a rule
| set through its version, a phase through its rule set, a reference through its rule, a membership
| through its group. So a phase id belonging to somebody else's rule system does not 403; it fails
| to resolve, and the request 404s before a handler runs.
|
| That is what lets these ids be opaque uuids in a URL without any of them being a capability. It is
| also why the children are not exposed at shorter top-level addresses — `/rule-sets/{id}`,
| `/game-phases/{id}` — as a flatter API design would suggest, and as section 40 of the module brief
| sketches. Reaching a rule set without its version would mean looking the parent up *from* the
| child, which is exactly the reverse-lookup pattern that turns a guessed id into cross-workspace
| access. GameEconomy, Playtesting and PrototypeIteration all made the same call for the same
| reason; see the notes at the top of routes/balance.php and routes/playtests.php.
|
| `{version}` is GameDesign's binding, reused rather than re-declared. Route binder names are global
| to the application, and a second claim on that one would break both GameDesign's own chain and
| DesignFramework's delegation through it. Seven other names here were widened around the same
| table: `{gameRule}` rather than `{rule}`, `{gamePhase}` rather than `{phase}` (DesignFramework's),
| `{ruleMechanic}` rather than `{mechanic}` (GameDesign's), `{ruleAction}` rather than `{action}`,
| `{ruleEffect}` rather than `{effect}` (GameEconomy's), and `{ruleCondition}` rather than
| `{condition}`. See `.ai/rules/providers.md`.
|
| Lifecycle changes are POSTs to named actions rather than a PATCH of a status field, because they
| are actions with rules — activating retires whichever rule set was in play and is refused while
| the validator reports errors, and archiving cannot be undone — rather than editable attributes.
|
| Three routes are worth pointing at:
|
| - `clone` is the module's answer to "I want to change the rules that are in play". An active rule
|   set refuses every write except this one, so it is the affordance section 55 of the brief
|   depends on rather than a convenience.
| - `analysis` is a POST that changes nothing. It exists because pressing "Analyse" is a fact about
|   how a studio works, and the dashboard's own reading of the same numbers deliberately does not
|   announce itself.
| - the `order` routes are POSTs taking a whole list rather than PATCHes moving one item, because
|   that is the shape a drag produces and the only shape that cannot go half-wrong. Each is
|   declared before the wildcard beneath it so the literal segment wins.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::prefix('workspaces/{workspace}/games/{game}/versions/{version}/rules')->group(function () {
        Route::get('/', [RuleSetController::class, 'index'])->name('rules.index');
        Route::post('/', [RuleSetController::class, 'store'])->name('rules.store');

        Route::prefix('{ruleSet}')->group(function () {
            Route::get('/', [RuleSetController::class, 'show'])->name('rules.show');
            Route::patch('/', [RuleSetController::class, 'update'])->name('rules.update');

            Route::post('activate', [RuleSetLifecycleController::class, 'activate'])->name('rules.activate');
            Route::post('archive', [RuleSetLifecycleController::class, 'archive'])->name('rules.archive');
            Route::post('clone', [RuleSetLifecycleController::class, 'clone'])->name('rules.clone');

            /*
             * The four screens beyond the dashboard.
             */
            Route::get('builder', [RuleBuilderController::class, 'show'])->name('rules.builder');
            Route::get('phases', [PhaseDesignerController::class, 'show'])->name('rules.phases');
            Route::get('graph', [RuleGraphController::class, 'show'])->name('rules.graph');
            Route::get('analysis', [RuleAnalysisController::class, 'show'])->name('rules.analysis');

            Route::post('analysis', [RuleAnalysisController::class, 'analyse'])->name('rules.analysis.store');
            Route::post('validate', [RuleAnalysisController::class, 'validateRuleSet'])->name('rules.validate');

            /*
             * The rules themselves, and how they relate to one another.
             */
            Route::post('rules', [GameRuleController::class, 'store'])->name('rules.rules.store');
            Route::post('rules/order', [GameRuleController::class, 'reorder'])->name('rules.rules.order');

            Route::prefix('rules/{gameRule}')->group(function () {
                Route::get('/', [GameRuleController::class, 'show'])->name('rules.rules.show');
                Route::patch('/', [GameRuleController::class, 'update'])->name('rules.rules.update');
                Route::delete('/', [GameRuleController::class, 'destroy'])->name('rules.rules.destroy');

                Route::post('references', [RuleReferenceController::class, 'store'])->name('rules.references.store');
                Route::delete('references/{reference}', [RuleReferenceController::class, 'destroy'])->name('rules.references.destroy');
            });

            /*
             * The mechanisms the rule system says it uses.
             */
            Route::post('mechanics', [RuleMechanicController::class, 'store'])->name('rules.mechanics.store');
            Route::post('mechanics/order', [RuleMechanicController::class, 'reorder'])->name('rules.mechanics.order');
            Route::patch('mechanics/{ruleMechanic}', [RuleMechanicController::class, 'update'])->name('rules.mechanics.update');
            Route::delete('mechanics/{ruleMechanic}', [RuleMechanicController::class, 'destroy'])->name('rules.mechanics.destroy');

            /*
             * The stages of play, and how play moves between them.
             */
            Route::post('phases', [GamePhaseController::class, 'store'])->name('rules.phases.store');
            Route::post('phases/order', [GamePhaseController::class, 'reorder'])->name('rules.phases.order');
            Route::patch('phases/{gamePhase}', [GamePhaseController::class, 'update'])->name('rules.phases.update');
            Route::delete('phases/{gamePhase}', [GamePhaseController::class, 'destroy'])->name('rules.phases.destroy');

            Route::post('transitions', [PhaseTransitionController::class, 'store'])->name('rules.transitions.store');
            Route::patch('transitions/{transition}', [PhaseTransitionController::class, 'update'])->name('rules.transitions.update');
            Route::delete('transitions/{transition}', [PhaseTransitionController::class, 'destroy'])->name('rules.transitions.destroy');

            /*
             * What players may do.
             */
            Route::post('actions', [RuleActionController::class, 'store'])->name('rules.actions.store');
            Route::post('actions/order', [RuleActionController::class, 'reorder'])->name('rules.actions.order');
            Route::get('actions/{ruleAction}', [RuleActionController::class, 'show'])->name('rules.actions.show');
            Route::patch('actions/{ruleAction}', [RuleActionController::class, 'update'])->name('rules.actions.update');
            Route::delete('actions/{ruleAction}', [RuleActionController::class, 'destroy'])->name('rules.actions.destroy');

            /*
             * What has to be true first, and what happens afterwards.
             *
             * Both hang off a rule or an action rather than off the rule set, but
             * both are addressed here: the owner arrives in the body, because a URL
             * carrying it would need two shapes — one under a rule and one under an
             * action — for records that are otherwise identical.
             */
            Route::post('requirements', [RuleRequirementController::class, 'store'])->name('rules.requirements.store');
            Route::patch('requirements/{requirement}', [RuleRequirementController::class, 'update'])->name('rules.requirements.update');
            Route::delete('requirements/{requirement}', [RuleRequirementController::class, 'destroy'])->name('rules.requirements.destroy');

            Route::post('effects', [RuleEffectController::class, 'store'])->name('rules.effects.store');
            Route::patch('effects/{ruleEffect}', [RuleEffectController::class, 'update'])->name('rules.effects.update');
            Route::delete('effects/{ruleEffect}', [RuleEffectController::class, 'destroy'])->name('rules.effects.destroy');

            /*
             * The named sentences everything else points at.
             */
            Route::post('conditions', [RuleConditionController::class, 'store'])->name('rules.conditions.store');
            Route::patch('conditions/{ruleCondition}', [RuleConditionController::class, 'update'])->name('rules.conditions.update');
            Route::delete('conditions/{ruleCondition}', [RuleConditionController::class, 'destroy'])->name('rules.conditions.destroy');

            Route::post('condition-groups', [ConditionGroupController::class, 'store'])->name('rules.condition-groups.store');

            Route::prefix('condition-groups/{conditionGroup}')->group(function () {
                Route::patch('/', [ConditionGroupController::class, 'update'])->name('rules.condition-groups.update');
                Route::delete('/', [ConditionGroupController::class, 'destroy'])->name('rules.condition-groups.destroy');

                Route::post('conditions', [ConditionGroupController::class, 'storeCondition'])->name('rules.condition-groups.conditions.store');
                Route::delete('conditions/{membership}', [ConditionGroupController::class, 'destroyCondition'])->name('rules.condition-groups.conditions.destroy');
            });

            Route::post('triggers', [RuleTriggerController::class, 'store'])->name('rules.triggers.store');
            Route::patch('triggers/{trigger}', [RuleTriggerController::class, 'update'])->name('rules.triggers.update');
            Route::delete('triggers/{trigger}', [RuleTriggerController::class, 'destroy'])->name('rules.triggers.destroy');

            /*
             * How the game is won, lost, and brought to a close.
             *
             * Three addresses rather than one with a `kind` parameter, because they
             * are three different questions a game answers at once — see section 26
             * of the module brief.
             */
            Route::post('victory-conditions', [OutcomeController::class, 'storeVictory'])->name('rules.victory-conditions.store');
            Route::patch('victory-conditions/{victoryCondition}', [OutcomeController::class, 'updateVictory'])->name('rules.victory-conditions.update');
            Route::delete('victory-conditions/{victoryCondition}', [OutcomeController::class, 'destroyVictory'])->name('rules.victory-conditions.destroy');

            Route::post('defeat-conditions', [OutcomeController::class, 'storeDefeat'])->name('rules.defeat-conditions.store');
            Route::patch('defeat-conditions/{defeatCondition}', [OutcomeController::class, 'updateDefeat'])->name('rules.defeat-conditions.update');
            Route::delete('defeat-conditions/{defeatCondition}', [OutcomeController::class, 'destroyDefeat'])->name('rules.defeat-conditions.destroy');

            Route::post('end-conditions', [OutcomeController::class, 'storeEnd'])->name('rules.end-conditions.store');
            Route::patch('end-conditions/{endCondition}', [OutcomeController::class, 'updateEnd'])->name('rules.end-conditions.update');
            Route::delete('end-conditions/{endCondition}', [OutcomeController::class, 'destroyEnd'])->name('rules.end-conditions.destroy');
        });
    });
});
