/**
 * The GameRules module's client surface.
 *
 * Pages under `resources/js/pages/rules` are thin wrappers over the page components here; Inertia requires
 * page components to live under `pages/`, so the reusable parts live in the feature instead.
 *
 * Three things are worth knowing before importing anything from here.
 *
 * **Every value is a string.** A condition's value, a requirement's threshold and an effect's amount are all
 * text — "+3", "half, rounded down", "all of them" — and nothing in this feature parses, sums or compares
 * one numerically. That is not an oversight: this module describes a board game and never plays one, so
 * there is nothing for a number to be used *for*.
 *
 * **Every economy field is a handle, not a cost.** What an action costs belongs to GameEconomy. This feature
 * stores `build` and `wood`, and shows whatever the balance profile says today. There is nowhere in these
 * components to type an amount, which is what keeps the rules screen and the balance screen from ever
 * disagreeing.
 *
 * **`canEdit` is false more often than you expect.** A rule set that is in play refuses every write, and
 * that is the module's central rule rather than an edge case. Anything that draws a control has to check it,
 * and anything that draws a *screen* has to offer the way forward — which is `canClone`.
 */

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
    removeConditionFromGroup,
    reorderActions,
    reorderMechanics,
    reorderPhases,
    reorderRules,
    RuleApiError,
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
} from './api';
export type { MutationOptions } from './api';
export { default as ActionEditor } from './components/action-editor';
export { default as ActionList } from './components/action-list';
export { default as ConditionBuilder } from './components/condition-builder';
export { default as ConditionGroupEditor } from './components/condition-group-editor';
export { default as CreateRuleSetDialog } from './components/create-rule-set-dialog';
export { default as EffectEditor } from './components/effect-editor';
export { default as MechanicForm } from './components/mechanic-form';
export { default as MechanicList } from './components/mechanic-list';
export { default as OptionSelect } from './components/option-select';
export { default as OutcomeEditor } from './components/outcome-editor';
export {
    DefeatConditionEditor,
    GameEndConditionEditor,
    VictoryConditionEditor,
} from './components/outcome-panels';
export { default as PhaseEditor } from './components/phase-editor';
export { default as PhaseList } from './components/phase-list';
export { default as PhaseTransitionEditor } from './components/phase-transition-editor';
export { default as RequirementEditor } from './components/requirement-editor';
export { default as RuleAnalysisPanel } from './components/rule-analysis';
export { default as RuleEditor } from './components/rule-editor';
export { default as RuleGraph } from './components/rule-graph';
export { default as RuleReferenceList } from './components/rule-reference-list';
export { default as RuleSetHeader } from './components/rule-set-header';
export { default as RuleSetList } from './components/rule-set-list';
export { default as RuleTree } from './components/rule-tree';
export { default as RuleValidation } from './components/rule-validation';
export {
    ReferenceTypeBadge,
    RuleSetStatusBadge,
    RuleStatusBadge,
    SeverityBadge,
} from './components/status-badges';
export { default as SummaryTiles } from './components/summary-tiles';
export { default as TriggerEditor } from './components/trigger-editor';
export { useRulePermissions } from './hooks/use-permissions';
export { useRuleForm } from './hooks/use-rule-form';
export type { FieldErrors, UseRuleFormResult } from './hooks/use-rule-form';
export { useRuleScope, useRuleSetScope } from './hooks/use-rule-scope';
export type { RuleScope, RuleSetScope } from './hooks/use-rule-scope';
export { flattenTree, usePhaseTree, useRuleTree } from './hooks/use-rule-tree';
export type { TreeNode } from './hooks/use-rule-tree';
export { default as PhaseDesignerPage } from './pages/phase-designer-page';
export { default as RuleActionPage } from './pages/rule-action-page';
export { default as RuleAnalysisPage } from './pages/rule-analysis-page';
export { default as RuleBuilderPage } from './pages/rule-builder-page';
export { default as RuleGraphPage } from './pages/rule-graph-page';
export { default as RulePage } from './pages/rule-page';
export { default as RuleSetsPage } from './pages/rule-sets-page';
export { default as RulesDashboardPage } from './pages/rules-dashboard-page';
export * from './schemas/game-rules';
export type * from './types/game-rules';
