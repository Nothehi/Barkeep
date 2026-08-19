/**
 * The GameEconomy module's client surface.
 *
 * Pages under `resources/js/pages/balance` are thin wrappers over the page components here; Inertia requires
 * page components to live under `pages/`, so the reusable parts live in the feature instead.
 *
 * One thing is worth knowing before importing anything from here: every amount in this module is a
 * **string**. The server stores exact decimals and does its arithmetic in base ten, and parsing them into
 * JavaScript numbers would reintroduce precisely the floating-point error the whole module exists to avoid.
 * Nothing here sums, averages or compares amounts numerically — anything that needs a total asks the server
 * for one.
 */

export {
    activateBalanceProfile,
    addActionCost,
    addActionEffect,
    addActionReward,
    analyseBalanceProfile,
    archiveBalanceProfile,
    archiveBalanceScenario,
    BalanceApiError,
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
} from './api';
export type { MutationOptions } from './api';
export { default as ActionCostEditor } from './components/action-cost-editor';
export { default as ActionEffectEditor } from './components/action-effect-editor';
export { default as ActionLineEditor } from './components/action-line-editor';
export { default as ActionRewardEditor } from './components/action-reward-editor';
export {
    default as Amount,
    isZeroAmount,
    toneForNet,
} from './components/amount';
export { default as BalanceAnalysisPanel } from './components/balance-analysis';
export { default as BalanceAssumptionList } from './components/balance-assumption-list';
export { default as BalanceComparisonView } from './components/balance-comparison';
export { default as BalanceObservationList } from './components/balance-observation-list';
export { default as BalanceProfileHeader } from './components/balance-profile-header';
export { default as BalanceProfileList } from './components/balance-profile-list';
export { default as BalanceScenarioList } from './components/balance-scenario-list';
export { default as BalanceSnapshotList } from './components/balance-snapshot-list';
export { default as BalanceVariableTable } from './components/balance-variable-table';
export { default as BalanceWarningList } from './components/balance-warning-list';
export { default as CreateBalanceProfileDialog } from './components/create-balance-profile-dialog';
export { default as EconomyActionForm } from './components/economy-action-form';
export { default as EconomyActionList } from './components/economy-action-list';
export { default as ResourceFlowDiagram } from './components/resource-flow-diagram';
export { default as ResourceFlowList } from './components/resource-flow-list';
export { default as ResourceForm } from './components/resource-form';
export { default as ResourceList } from './components/resource-list';
export {
    BalanceProfileStatusBadge,
    BalanceScenarioStatusBadge,
    FlowTypeBadge,
    ObservationSeverityBadge,
    SnapshotChangeBadge,
    WarningSeverityBadge,
} from './components/status-badges';
export { useBalanceForm } from './hooks/use-balance-form';
export type {
    FieldErrors,
    UseBalanceFormResult,
} from './hooks/use-balance-form';
export { useBalanceScope, useProfileScope } from './hooks/use-balance-scope';
export type { BalanceScope, ProfileScope } from './hooks/use-balance-scope';
export { useBalancePermissions } from './hooks/use-permissions';
export { default as BalanceComparisonPage } from './pages/balance-comparison-page';
export { default as BalanceDashboardPage } from './pages/balance-dashboard-page';
export { default as BalanceProfilesPage } from './pages/balance-profiles-page';
export { default as EconomyActionPage } from './pages/economy-action-page';
export { default as ResourcePage } from './pages/resource-page';
export * from './schemas/game-economy';
export type {
    ActionEffect,
    ActionEffectType,
    ActionLine,
    ActionProfitability,
    AssumptionCategory,
    AssumptionConfidence,
    BalanceAnalysis,
    BalanceAssumption,
    BalanceComparison,
    BalanceEntityType,
    BalanceObservation,
    BalanceOptions,
    BalanceProfile,
    BalanceProfilePermissions,
    BalanceProfileStatus,
    BalanceScenario,
    BalanceScenarioStatus,
    BalanceSnapshot,
    BalanceSummary,
    BalanceVariable,
    BalanceVariableCategory,
    BalanceWarning,
    BalanceWarningSeverity,
    ConversionRatio,
    DescribedOption,
    EconomyAction,
    FieldChange,
    ObservationSeverity,
    ObservationSourceType,
    ResourceCategory,
    ResourceDelta,
    ResourceFlow,
    ResourceFlowType,
    ResourceNetFlow,
    ResourceType,
    ScenarioVariable,
    SnapshotChange,
    SnapshotChangeType,
    Transition,
    VocabularyOption,
} from './types/game-economy';
