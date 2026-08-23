/**
 * The rules shapes the server sends.
 *
 * Mirrors the resources under `Modules\GameRules\Presentation\Http\Resources`. Those are the authoritative
 * shape — when one changes, change it here too.
 *
 * Four things in here are worth reading before using them.
 *
 * Every `*_label` is already worded by an enum in the domain, so the client renders what it is given rather
 * than keeping a second copy of the vocabulary that would drift the first time a rule type was renamed.
 *
 * Every `permissions` map is the policy's own answer, which decides what the interface *offers* and never
 * what the server allows — each ability is checked again on the request that performs the action.
 * `canEdit` is false for a rule set that is in play, and `canClone` is the affordance that replaces it.
 *
 * Every `value` is a **string**, and never a number. "+3", "half, rounded down" and "all of them" are all
 * things a rulebook says, and nothing anywhere in this module computes with one — see section 33 of the
 * module brief on why nothing executes a rule.
 *
 * And every `economy_*_slug` is a handle into GameEconomy, not a cost. What something costs is read live
 * from the balance profile and arrives as `EconomyReference`; this feature never stores or sums a cost.
 */

import type { User } from '@/features/auth';
import type { GameVersion } from '@/features/games';

/**
 * Where a rule set sits in its own life. Archived is terminal — a studio returning to an older rule system
 * clones it into a new draft.
 */
export type RuleSetStatus = 'draft' | 'active' | 'archived';

/**
 * How settled one rule, phase or action is. Deprecated is the case that earns the enum: rules do not stop
 * existing when a studio decides against them, they stop applying.
 */
export type RuleStatus = 'draft' | 'active' | 'deprecated';

/** What part of the game a rule governs. Classification only — nothing behaves differently because of it. */
export type RuleType =
    | 'general'
    | 'setup'
    | 'turn'
    | 'action'
    | 'movement'
    | 'combat'
    | 'resource'
    | 'scoring'
    | 'victory'
    | 'defeat'
    | 'end_game'
    | 'player_interaction'
    | 'special';

export type MechanicCategory =
    | 'action'
    | 'resource'
    | 'card'
    | 'dice'
    | 'player_interaction'
    | 'movement'
    | 'combat'
    | 'economy'
    | 'scoring'
    | 'information'
    | 'progression'
    | 'other';

/**
 * What kind of stage of *play* a phase is. Not DesignFramework's design phase, which is a stage of the
 * designer's work and belongs to a different module entirely.
 */
export type GamePhaseType =
    | 'setup'
    | 'round'
    | 'turn'
    | 'action'
    | 'resolution'
    | 'cleanup'
    | 'end_game'
    | 'special';

export type RuleActionType =
    | 'basic'
    | 'movement'
    | 'combat'
    | 'resource'
    | 'card'
    | 'build'
    | 'trade'
    | 'special'
    | 'free'
    | 'reaction'
    | 'pass';

export type RequirementType =
    | 'resource'
    | 'player_state'
    | 'game_state'
    | 'phase'
    | 'turn'
    | 'position'
    | 'ownership'
    | 'card'
    | 'custom';

export type ConditionType =
    | 'resource'
    | 'counter'
    | 'player_count'
    | 'phase'
    | 'turn'
    | 'score'
    | 'ownership'
    | 'card'
    | 'game_state'
    | 'custom';

/**
 * How a condition's subject is compared to its value. `is_true` and `is_false` take no value, which is the
 * one shape the condition builder has to know about.
 */
export type ConditionOperator =
    | 'equals'
    | 'not_equals'
    | 'greater_than'
    | 'greater_than_or_equal'
    | 'less_than'
    | 'less_than_or_equal'
    | 'is_true'
    | 'is_false'
    | 'in'
    | 'not_in';

/** How the conditions in a group combine. Two cases, and staying two — there is no nesting. */
export type LogicOperator = 'and' | 'or';

export type EffectType =
    | 'resource'
    | 'movement'
    | 'draw'
    | 'discard'
    | 'score'
    | 'damage'
    | 'heal'
    | 'state_change'
    | 'unlock'
    | 'lock'
    | 'turn_change'
    | 'phase_change'
    | 'end_game'
    | 'custom';

export type TriggerType =
    | 'game_start'
    | 'round_start'
    | 'round_end'
    | 'turn_start'
    | 'turn_end'
    | 'phase_start'
    | 'phase_end'
    | 'action_executed'
    | 'condition_met'
    | 'resource_changed'
    | 'score_changed'
    | 'player_event'
    | 'game_state_changed'
    | 'custom';

/** How one rule relates to another. Only the directed kinds are followed when looking for a loop. */
export type ReferenceType =
    'depends_on' | 'modifies' | 'overrides' | 'exception_to' | 'related_to';

/**
 * How seriously to take something the validator found. An error is a shape that cannot be a working rule
 * system under any reading; a warning is one a designer might have meant, or not got to yet.
 */
export type ValidationSeverity = 'warning' | 'error';

/** What kind of record a finding or a graph node is about, so the interface knows where to link. */
export type RuleEntityType =
    | 'rule_set'
    | 'rule'
    | 'mechanic'
    | 'phase'
    | 'transition'
    | 'action'
    | 'requirement'
    | 'condition'
    | 'condition_group'
    | 'effect'
    | 'trigger'
    | 'victory_condition'
    | 'defeat_condition'
    | 'game_end_condition'
    | 'reference';

export type VocabularyOption = { value: string; label: string };
export type DescribedOption = VocabularyOption & { description: string };

export type PhaseTypeOption = DescribedOption & {
    is_entry: boolean;
    is_terminal: boolean;
};

export type OperatorOption = VocabularyOption & {
    symbol: string;
    expects_value: boolean;
    expects_list: boolean;
    expects_number: boolean;
};

export type EffectTypeOption = DescribedOption & {
    expects_value: boolean;
    is_economic: boolean;
    moves_play: boolean;
};

export type RequirementTypeOption = DescribedOption & { is_economic: boolean };
export type ReferenceTypeOption = DescribedOption & { is_directed: boolean };

/**
 * The whole vocabulary, worded by the server.
 *
 * Every picker on these screens renders from this rather than from a list of its own, so a taxonomy renamed
 * in the domain reads the new way here without anything in TypeScript changing.
 */
export type RuleOptions = {
    rule_set_statuses: VocabularyOption[];
    rule_statuses: DescribedOption[];
    rule_types: DescribedOption[];
    mechanic_categories: DescribedOption[];
    phase_types: PhaseTypeOption[];
    action_types: DescribedOption[];
    requirement_types: RequirementTypeOption[];
    condition_types: DescribedOption[];
    operators: OperatorOption[];
    logic_operators: DescribedOption[];
    effect_types: EffectTypeOption[];
    trigger_types: DescribedOption[];
    reference_types: ReferenceTypeOption[];
};

export type Transition = { status: RuleSetStatus; label: string };

/**
 * What the signed in account may do with a rule set.
 *
 * `canEdit` gates nearly every control. `canClone` is the one that matters on an active rule set, where
 * every other write is refused — an interface that only knew `canEdit` would show a read-only screen with
 * no way forward.
 */
export type RuleSetPermissions = {
    canView: boolean;
    canRename: boolean;
    canEdit: boolean;
    canActivate: boolean;
    canArchive: boolean;
    canClone: boolean;
};

export type RuleSet = {
    id: string;
    game_version_id: string;
    name: string;
    description: string | null;
    status: RuleSetStatus;
    status_label: string;
    is_editable: boolean;
    cloned_from_rule_set_id: string | null;
    available_transitions: Transition[];
    version?: GameVersion;
    creator?: User;
    rules_count?: number;
    mechanics_count?: number;
    phases_count?: number;
    actions_count?: number;
    conditions_count?: number;
    permissions?: RuleSetPermissions;
    created_at: string | null;
    updated_at: string | null;
};

export type GameRule = {
    id: string;
    rule_set_id: string;
    parent_rule_id: string | null;
    phase_id: string | null;
    name: string;
    slug: string;
    description: string | null;
    rule_type: RuleType;
    rule_type_label: string;
    status: RuleStatus;
    status_label: string;
    position: number;
    phase?: GamePhase;
    children?: GameRule[];
    requirements?: RuleRequirement[];
    effects?: RuleEffect[];
    references?: RuleReference[];
    children_count?: number;
    requirements_count?: number;
    effects_count?: number;
    references_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A mechanism *this* rule system uses, in the studio's own words.
 *
 * Not an entry in GameDesign's shared vocabulary of design terms, which is seeded, curated and translated.
 * The two have similar names and mean different things.
 */
export type RuleMechanic = {
    id: string;
    rule_set_id: string;
    name: string;
    slug: string;
    description: string | null;
    category: MechanicCategory;
    category_label: string;
    position: number;
    created_at: string | null;
    updated_at: string | null;
};

export type GamePhase = {
    id: string;
    rule_set_id: string;
    parent_phase_id: string | null;
    name: string;
    slug: string;
    description: string | null;
    phase_type: GamePhaseType;
    phase_type_label: string;
    status: RuleStatus;
    status_label: string;
    is_entry: boolean;
    is_terminal: boolean;
    position: number;
    children?: GamePhase[];
    actions?: RuleAction[];
    children_count?: number;
    actions_count?: number;
    rules_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

export type PhaseTransition = {
    id: string;
    rule_set_id: string;
    from_phase_id: string;
    to_phase_id: string;
    from_phase_name: string | null;
    to_phase_name: string | null;
    condition_id: string | null;
    condition_statement: string | null;
    trigger_id: string | null;
    trigger_name: string | null;
    is_guarded: boolean;
    position: number;
    created_at: string | null;
    updated_at: string | null;
};

export type RuleAction = {
    id: string;
    rule_set_id: string;
    phase_id: string | null;
    name: string;
    slug: string;
    description: string | null;
    action_type: RuleActionType;
    action_type_label: string;
    status: RuleStatus;
    status_label: string;
    economy_action_slug: string | null;
    position: number;
    phase?: GamePhase;
    requirements?: RuleRequirement[];
    effects?: RuleEffect[];
    requirements_count?: number;
    effects_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

export type RuleRequirement = {
    id: string;
    rule_set_id: string;
    rule_id: string | null;
    action_id: string | null;
    requirement_type: RequirementType;
    requirement_type_label: string;
    description: string;
    value: string | null;
    economy_resource_slug: string | null;
    position: number;
    created_at: string | null;
    updated_at: string | null;
};

export type RuleEffect = {
    id: string;
    rule_set_id: string;
    rule_id: string | null;
    action_id: string | null;
    effect_type: EffectType;
    effect_type_label: string;
    target: string;
    value: string | null;
    description: string | null;
    economy_resource_slug: string | null;
    moves_play: boolean;
    position: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A named, reusable logical requirement. `statement` is the three parts read as one sentence, built on the
 * server because two of the three come from enums that word themselves.
 */
export type RuleCondition = {
    id: string;
    rule_set_id: string;
    name: string;
    description: string | null;
    condition_type: ConditionType;
    condition_type_label: string;
    operator: ConditionOperator;
    operator_label: string;
    operator_symbol: string;
    expects_value: boolean;
    value: string | null;
    statement: string;
    created_at: string | null;
    updated_at: string | null;
};

export type ConditionGroupMembership = {
    id: string;
    condition_group_id: string;
    condition_id: string;
    position: number;
    condition?: RuleCondition;
};

export type ConditionGroup = {
    id: string;
    rule_set_id: string;
    name: string;
    description: string | null;
    logic_operator: LogicOperator;
    logic_operator_label: string;
    joiner: string;
    conditions?: RuleCondition[];
    memberships?: ConditionGroupMembership[];
    conditions_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

export type RuleTrigger = {
    id: string;
    rule_set_id: string;
    name: string;
    description: string | null;
    trigger_type: TriggerType;
    trigger_type_label: string;
    position: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A victory, defeat or end condition. The three are separate records on the server for a reason — winning,
 * losing and stopping are three different questions — and share a payload because their fields are
 * identical.
 */
export type Outcome = {
    id: string;
    rule_set_id: string;
    name: string;
    description: string | null;
    condition_id: string | null;
    condition_statement: string | null;
    is_measurable: boolean;
    priority: number;
    created_at: string | null;
    updated_at: string | null;
};

export type RuleReference = {
    id: string;
    rule_id: string;
    referenced_rule_id: string;
    rule_name: string | null;
    referenced_rule_name: string | null;
    reference_type: ReferenceType;
    reference_type_label: string;
    is_directed: boolean;
    description: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type ValidationError = {
    code: string;
    severity: ValidationSeverity;
    severity_label: string;
    entity_type: RuleEntityType;
    entity_type_label: string;
    entity_id: string | null;
    subject: string;
    title: string;
    message: string;
    explanation: string;
};

export type RuleSetSummary = {
    rules: number;
    mechanics: number;
    phases: number;
    transitions: number;
    actions: number;
    requirements: number;
    conditions: number;
    condition_groups: number;
    effects: number;
    triggers: number;
    victory_conditions: number;
    defeat_conditions: number;
    end_conditions: number;
    references: number;
    warnings: number;
    errors: number;
    is_empty: boolean;
    has_errors: boolean;
};

export type RuleGraphNode = {
    key: string;
    entity_type: RuleEntityType;
    entity_id: string | null;
    label: string;
    detail: string | null;
    is_entry: boolean;
    is_terminal: boolean;
    is_reachable: boolean;
    actions: string[];
};

export type RuleGraphEdge = {
    from: string;
    to: string;
    label: string | null;
    entity_id: string | null;
    is_implicit: boolean;
};

export type RuleGraph = {
    nodes: RuleGraphNode[];
    edges: RuleGraphEdge[];
    unreachable: string[];
    is_empty: boolean;
};

/**
 * Everything the dashboard draws, in one payload. The sections are not independent — the findings are
 * *about* the rules, the phases and the actions — so they arrive together.
 */
export type RuleSetAnalysis = {
    summary: RuleSetSummary;
    rules: GameRule[];
    mechanics: RuleMechanic[];
    phases: GamePhase[];
    transitions: PhaseTransition[];
    actions: RuleAction[];
    requirements: RuleRequirement[];
    conditions: RuleCondition[];
    condition_groups: ConditionGroup[];
    effects: RuleEffect[];
    triggers: RuleTrigger[];
    references: RuleReference[];
    victory_conditions: Outcome[];
    defeat_conditions: Outcome[];
    end_conditions: Outcome[];
    graph: RuleGraph;
    errors: ValidationError[];
    warnings: ValidationError[];
};

/**
 * What the game's economy says about a handle a rule points at. `is_resolved` being false is ordinary: most
 * rule sets are written before an economy is modelled, and many studios never model one.
 */
export type EconomyReference = {
    handle: string;
    is_resolved: boolean;
    label: string | null;
    summary: string | null;
};

/**
 * What the version's active balance profile offers a rule to point at.
 *
 * `available` is false when there is no active profile, and the interface then draws no economy pickers at
 * all — which is the right answer for most rule sets rather than an error.
 */
export type EconomyChoices = {
    available: boolean;
    actions: { handle: string; label: string }[];
    resources: { handle: string; label: string }[];
};
