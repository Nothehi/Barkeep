import { router } from '@inertiajs/react';
import rules from '@/routes/rules';
import type {
    ActionInput,
    ConditionGroupInput,
    ConditionInput,
    EffectInput,
    MechanicInput,
    OutcomeInput,
    PhaseInput,
    ReferenceInput,
    RequirementInput,
    RuleInput,
    RuleSetInput,
    TransitionInput,
    TriggerInput,
} from '../schemas/game-rules';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Every write this feature performs, as Inertia visits.
 *
 * None of these returns anything. The server answers each with a redirect and a flash message, so the
 * reloaded page brings the new record, the recomputed counts and the refreshed findings together — see
 * `./mutation` for why that matters on a screen that shows the same rule system eight ways at once.
 *
 * ## Empty strings, not nulls
 *
 * The optional reference fields go out as whatever the form holds, including `''`. That is deliberate: the
 * server reads an empty string as "not set" and a *missing* key as "leave it alone", which is the
 * distinction every partial update rests on. A form that sent `null` for an untouched select would clear a
 * phase the designer never looked at.
 */

type Scope = { workspace: string; game: string; version: number };
type RuleSetScope = Scope & { ruleSet: string };

/*
 |--------------------------------------------------------------------------
 | Rule sets
 |--------------------------------------------------------------------------
 */

export function createRuleSet(
    scope: Scope,
    input: RuleSetInput,
    options: MutationOptions = {},
): void {
    router.post(rules.store.url(scope), { ...input }, toVisitOptions(options));
}

export function updateRuleSet(
    scope: RuleSetScope,
    input: Partial<RuleSetInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function activateRuleSet(
    scope: RuleSetScope,
    options: MutationOptions = {},
): void {
    router.post(rules.activate.url(scope), {}, toVisitOptions(options));
}

export function archiveRuleSet(
    scope: RuleSetScope,
    options: MutationOptions = {},
): void {
    router.post(rules.archive.url(scope), {}, toVisitOptions(options));
}

/**
 * Copy a rule set into a fresh draft.
 *
 * The name is optional and usually omitted: cloning is what an active rule set offers instead of editing, so
 * it has to work with one press. The server picks a name the version does not already use.
 */
export function cloneRuleSet(
    scope: RuleSetScope,
    input: Partial<RuleSetInput> = {},
    options: MutationOptions = {},
): void {
    router.post(rules.clone.url(scope), { ...input }, toVisitOptions(options));
}

/*
 |--------------------------------------------------------------------------
 | Rules
 |--------------------------------------------------------------------------
 */

export function createRule(
    scope: RuleSetScope,
    input: RuleInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.rules.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateRule(
    scope: RuleSetScope & { gameRule: string },
    input: Partial<RuleInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.rules.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteRule(
    scope: RuleSetScope & { gameRule: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.rules.destroy.url(scope), toVisitOptions(options));
}

/**
 * Put the rules into the order they were dragged into.
 *
 * The whole list, not one id and an index — that is the shape a drag produces and the only shape that cannot
 * go half-wrong.
 */
export function reorderRules(
    scope: RuleSetScope,
    ruleIds: string[],
    options: MutationOptions = {},
): void {
    router.post(
        rules.rules.order.url(scope),
        { rule_ids: ruleIds },
        toVisitOptions(options),
    );
}

export function createRuleReference(
    scope: RuleSetScope & { gameRule: string },
    input: ReferenceInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.references.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteRuleReference(
    scope: RuleSetScope & { gameRule: string; reference: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.references.destroy.url(scope), toVisitOptions(options));
}

/*
 |--------------------------------------------------------------------------
 | Mechanics
 |--------------------------------------------------------------------------
 */

export function createMechanic(
    scope: RuleSetScope,
    input: MechanicInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.mechanics.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateMechanic(
    scope: RuleSetScope & { ruleMechanic: string },
    input: Partial<MechanicInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.mechanics.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteMechanic(
    scope: RuleSetScope & { ruleMechanic: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.mechanics.destroy.url(scope), toVisitOptions(options));
}

export function reorderMechanics(
    scope: RuleSetScope,
    mechanicIds: string[],
    options: MutationOptions = {},
): void {
    router.post(
        rules.mechanics.order.url(scope),
        { mechanic_ids: mechanicIds },
        toVisitOptions(options),
    );
}

/*
 |--------------------------------------------------------------------------
 | Phases and transitions
 |--------------------------------------------------------------------------
 */

export function createPhase(
    scope: RuleSetScope,
    input: PhaseInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.phases.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updatePhase(
    scope: RuleSetScope & { gamePhase: string },
    input: Partial<PhaseInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.phases.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deletePhase(
    scope: RuleSetScope & { gamePhase: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.phases.destroy.url(scope), toVisitOptions(options));
}

/**
 * Put the phases into the order play visits them.
 *
 * The one reorder in this module that changes what the rules *say* rather than how they are displayed.
 */
export function reorderPhases(
    scope: RuleSetScope,
    phaseIds: string[],
    options: MutationOptions = {},
): void {
    router.post(
        rules.phases.order.url(scope),
        { phase_ids: phaseIds },
        toVisitOptions(options),
    );
}

export function createTransition(
    scope: RuleSetScope,
    input: TransitionInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.transitions.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateTransition(
    scope: RuleSetScope & { transition: string },
    input: Partial<TransitionInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.transitions.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteTransition(
    scope: RuleSetScope & { transition: string },
    options: MutationOptions = {},
): void {
    router.delete(
        rules.transitions.destroy.url(scope),
        toVisitOptions(options),
    );
}

/*
 |--------------------------------------------------------------------------
 | Actions
 |--------------------------------------------------------------------------
 */

export function createAction(
    scope: RuleSetScope,
    input: ActionInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.actions.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateAction(
    scope: RuleSetScope & { ruleAction: string },
    input: Partial<ActionInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.actions.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteAction(
    scope: RuleSetScope & { ruleAction: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.actions.destroy.url(scope), toVisitOptions(options));
}

export function reorderActions(
    scope: RuleSetScope,
    actionIds: string[],
    options: MutationOptions = {},
): void {
    router.post(
        rules.actions.order.url(scope),
        { action_ids: actionIds },
        toVisitOptions(options),
    );
}

/*
 |--------------------------------------------------------------------------
 | Requirements and effects
 |--------------------------------------------------------------------------
 */

export function createRequirement(
    scope: RuleSetScope,
    input: RequirementInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.requirements.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateRequirement(
    scope: RuleSetScope & { requirement: string },
    input: Partial<RequirementInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.requirements.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteRequirement(
    scope: RuleSetScope & { requirement: string },
    options: MutationOptions = {},
): void {
    router.delete(
        rules.requirements.destroy.url(scope),
        toVisitOptions(options),
    );
}

export function createEffect(
    scope: RuleSetScope,
    input: EffectInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.effects.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateEffect(
    scope: RuleSetScope & { ruleEffect: string },
    input: Partial<EffectInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.effects.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteEffect(
    scope: RuleSetScope & { ruleEffect: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.effects.destroy.url(scope), toVisitOptions(options));
}

/*
 |--------------------------------------------------------------------------
 | Conditions, groups and triggers
 |--------------------------------------------------------------------------
 */

export function createCondition(
    scope: RuleSetScope,
    input: ConditionInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.conditions.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateCondition(
    scope: RuleSetScope & { ruleCondition: string },
    input: Partial<ConditionInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.conditions.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteCondition(
    scope: RuleSetScope & { ruleCondition: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.conditions.destroy.url(scope), toVisitOptions(options));
}

export function createConditionGroup(
    scope: RuleSetScope,
    input: ConditionGroupInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.conditionGroups.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateConditionGroup(
    scope: RuleSetScope & { conditionGroup: string },
    input: Partial<ConditionGroupInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.conditionGroups.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteConditionGroup(
    scope: RuleSetScope & { conditionGroup: string },
    options: MutationOptions = {},
): void {
    router.delete(
        rules.conditionGroups.destroy.url(scope),
        toVisitOptions(options),
    );
}

export function addConditionToGroup(
    scope: RuleSetScope & { conditionGroup: string },
    conditionId: string,
    options: MutationOptions = {},
): void {
    router.post(
        rules.conditionGroups.conditions.store.url(scope),
        { condition_id: conditionId },
        toVisitOptions(options),
    );
}

/**
 * Take a condition out of a group.
 *
 * Addressed by *membership* rather than by condition, because the same condition may be in several groups
 * and detaching it from one must not touch the others.
 */
export function removeConditionFromGroup(
    scope: RuleSetScope & { conditionGroup: string; membership: string },
    options: MutationOptions = {},
): void {
    router.delete(
        rules.conditionGroups.conditions.destroy.url(scope),
        toVisitOptions(options),
    );
}

export function createTrigger(
    scope: RuleSetScope,
    input: TriggerInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.triggers.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateTrigger(
    scope: RuleSetScope & { trigger: string },
    input: Partial<TriggerInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.triggers.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteTrigger(
    scope: RuleSetScope & { trigger: string },
    options: MutationOptions = {},
): void {
    router.delete(rules.triggers.destroy.url(scope), toVisitOptions(options));
}

/*
 |--------------------------------------------------------------------------
 | Outcomes
 |--------------------------------------------------------------------------
 |
 | Three sets of calls rather than one with a `kind` argument, because winning,
 | losing and stopping are three different questions a game answers at once.
 */

export function createVictoryCondition(
    scope: RuleSetScope,
    input: OutcomeInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.victoryConditions.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateVictoryCondition(
    scope: RuleSetScope & { victoryCondition: string },
    input: Partial<OutcomeInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.victoryConditions.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteVictoryCondition(
    scope: RuleSetScope & { victoryCondition: string },
    options: MutationOptions = {},
): void {
    router.delete(
        rules.victoryConditions.destroy.url(scope),
        toVisitOptions(options),
    );
}

export function createDefeatCondition(
    scope: RuleSetScope,
    input: OutcomeInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.defeatConditions.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateDefeatCondition(
    scope: RuleSetScope & { defeatCondition: string },
    input: Partial<OutcomeInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.defeatConditions.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteDefeatCondition(
    scope: RuleSetScope & { defeatCondition: string },
    options: MutationOptions = {},
): void {
    router.delete(
        rules.defeatConditions.destroy.url(scope),
        toVisitOptions(options),
    );
}

export function createGameEndCondition(
    scope: RuleSetScope,
    input: OutcomeInput,
    options: MutationOptions = {},
): void {
    router.post(
        rules.endConditions.store.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateGameEndCondition(
    scope: RuleSetScope & { endCondition: string },
    input: Partial<OutcomeInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        rules.endConditions.update.url(scope),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteGameEndCondition(
    scope: RuleSetScope & { endCondition: string },
    options: MutationOptions = {},
): void {
    router.delete(
        rules.endConditions.destroy.url(scope),
        toVisitOptions(options),
    );
}

/*
 |--------------------------------------------------------------------------
 | Analysis
 |--------------------------------------------------------------------------
 |
 | Both announce that somebody looked, which is the only reason they are POSTs.
 | Neither writes anything: the dashboard reads exactly the same numbers on every
 | render, silently.
 */

export function analyseRuleSet(
    scope: RuleSetScope,
    options: MutationOptions = {},
): void {
    router.post(rules.analysis.store.url(scope), {}, toVisitOptions(options));
}

export function validateRuleSet(
    scope: RuleSetScope,
    options: MutationOptions = {},
): void {
    router.post(rules.validate.url(scope), {}, toVisitOptions(options));
}
