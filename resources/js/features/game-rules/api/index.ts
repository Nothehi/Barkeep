/**
 * The rules feature's server calls.
 *
 * Split by direction rather than by resource:
 *
 * - reads go over the JSON API and return data (`get*`);
 * - writes are Inertia visits and return nothing, because the server answers them with a redirect and,
 *   where it is worth saying, a flash message.
 *
 * The writes matter more than usual here. The rules dashboard shows the same rule system eight ways at once
 * — the counts, the rule tree, the phases, the actions, the conditions, the outcomes, the graph and the
 * findings — and almost every write moves several of them. Drawing one transition can turn an unreachable
 * phase into a reachable one and remove two findings three sections further down. Coming back as a reloaded
 * page is what keeps them agreeing with each other and with the server.
 */

export { RuleApiError } from './client';
export type { MutationOptions } from './mutation';
export {
    getConditionGroups,
    getConditions,
    getDefeatConditions,
    getEffects,
    getGameEndConditions,
    getMechanics,
    getPhases,
    getRequirements,
    getRule,
    getRuleAction,
    getRuleActions,
    getRuleGraph,
    getRuleReferences,
    getRules,
    getRuleSet,
    getRuleSetAnalysis,
    getRuleSets,
    getTransitions,
    getTriggers,
    getVictoryConditions,
} from './reads';
export {
    activateRuleSet,
    addConditionToGroup,
    analyseRuleSet,
    archiveRuleSet,
    cloneRuleSet,
    createAction,
    createCondition,
    createConditionGroup,
    createDefeatCondition,
    createEffect,
    createGameEndCondition,
    createMechanic,
    createPhase,
    createRequirement,
    createRule,
    createRuleReference,
    createRuleSet,
    createTransition,
    createTrigger,
    createVictoryCondition,
    deleteAction,
    deleteCondition,
    deleteConditionGroup,
    deleteDefeatCondition,
    deleteEffect,
    deleteGameEndCondition,
    deleteMechanic,
    deletePhase,
    deleteRequirement,
    deleteRule,
    deleteRuleReference,
    deleteTransition,
    deleteTrigger,
    deleteVictoryCondition,
    removeConditionFromGroup,
    reorderActions,
    reorderMechanics,
    reorderPhases,
    reorderRules,
    updateAction,
    updateCondition,
    updateConditionGroup,
    updateDefeatCondition,
    updateEffect,
    updateGameEndCondition,
    updateMechanic,
    updatePhase,
    updateRequirement,
    updateRule,
    updateRuleSet,
    updateTransition,
    updateTrigger,
    updateVictoryCondition,
    validateRuleSet,
} from './writes';
