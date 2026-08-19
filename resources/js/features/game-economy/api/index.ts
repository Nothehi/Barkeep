/**
 * The balance feature's server calls.
 *
 * Split by direction rather than by resource:
 *
 * - reads go over the JSON API and return data (`get*`);
 * - writes are Inertia visits and return nothing, because the server answers them with a redirect and,
 *   where it is worth saying, a flash message.
 *
 * The writes matter more than usual here. The balance dashboard shows the same configuration five ways at
 * once — the summary counts, the resources with their net flows, the actions, the variable table and the
 * findings — and every write moves all five. Changing one variable can turn an error into a clean analysis.
 * Coming back as a reloaded page is what keeps them agreeing with each other and with the server.
 */

export { BalanceApiError } from './client';
export type { MutationOptions } from './mutation';
export {
    getBalanceAnalysis,
    getBalanceAssumptions,
    getBalanceObservations,
    getBalanceProfile,
    getBalanceProfiles,
    getBalanceScenarios,
    getBalanceSnapshots,
    getBalanceVariables,
    getEconomyAction,
    getEconomyActions,
    getResourceFlows,
    getResources,
} from './reads';
export {
    activateBalanceProfile,
    addActionCost,
    addActionEffect,
    addActionReward,
    analyseBalanceProfile,
    archiveBalanceProfile,
    archiveBalanceScenario,
    comparisonUrl,
    createBalanceAssumption,
    createBalanceObservation,
    createBalanceProfile,
    createBalanceScenario,
    createBalanceSnapshot,
    createBalanceVariable,
    createEconomyAction,
    createFlow,
    createResource,
    deleteBalanceVariable,
    deleteEconomyAction,
    deleteFlow,
    deleteResource,
    removeActionCost,
    removeActionEffect,
    removeActionReward,
    removeScenarioVariable,
    setScenarioVariable,
    updateActionCost,
    updateActionEffect,
    updateActionReward,
    updateBalanceAssumption,
    updateBalanceObservation,
    updateBalanceProfile,
    updateBalanceScenario,
    updateBalanceVariable,
    updateEconomyAction,
    updateFlow,
    updateResource,
} from './writes';
