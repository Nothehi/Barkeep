/**
 * The balance shapes the server sends.
 *
 * Mirrors the resources under `Modules\GameEconomy\Presentation\Http\Resources`. Those are the
 * authoritative shape — when one changes, change it here too.
 *
 * Three things in here are worth reading before using them.
 *
 * Every `*_label` is already worded by an enum in the domain, so the client renders what it is given rather
 * than keeping a second copy of the vocabulary that would drift the first time a category was renamed.
 *
 * Every `permissions` map is the policy's own answer, which decides what the interface *offers* and never
 * what the server allows — each ability is checked again on the request that performs the action.
 *
 * And every amount is a **string**, not a number. That is the most important line in this file: the server
 * stores exact decimals and does its arithmetic in base ten, and parsing them into JavaScript numbers here
 * would reintroduce exactly the floating-point error the whole module exists to avoid. Amounts are rendered,
 * compared as strings, and never summed on the client — anything that needs a total asks the server for one.
 */

import type { User } from '@/features/auth';
import type { GameVersion } from '@/features/games';

/**
 * Where a balance configuration sits in its own life. Archived is terminal — a studio returning to an old
 * shape copies it into a new draft.
 */
export type BalanceProfileStatus = 'draft' | 'active' | 'archived';

/**
 * Where a hypothetical sits in its own life. Deliberately the same three words as a profile's and a
 * different type, because any number of scenarios may be active at once and only one profile may.
 */
export type BalanceScenarioStatus = 'draft' | 'active' | 'archived';

/**
 * What kind of thing a resource is. Classification only — what a resource can *do* is stated by its own
 * flags, because one designer's gold behaves nothing like the next one's.
 */
export type ResourceCategory =
    | 'currency'
    | 'material'
    | 'action'
    | 'victory'
    | 'information'
    | 'capacity'
    | 'health'
    | 'progression'
    | 'other';

/**
 * How a resource moves. The direction lives here rather than in the sign of the amount, so a flow can never
 * contradict itself.
 */
export type ResourceFlowType =
    | 'generation'
    | 'consumption'
    | 'conversion'
    | 'transfer'
    | 'loss'
    | 'reward'
    | 'penalty';

/**
 * What an action does that is not a quantity of a resource.
 */
export type ActionEffectType =
    'resource_modifier' | 'capacity_modifier' | 'unlock' | 'block' | 'other';

/**
 * What sort of number a balance variable is. `probability` is the one the analysis treats specially:
 * probabilities are written between 0 and 1 throughout, never as percentages.
 */
export type BalanceVariableCategory =
    | 'starting_value'
    | 'cost'
    | 'reward'
    | 'production'
    | 'capacity'
    | 'threshold'
    | 'timing'
    | 'probability'
    | 'other';

export type AssumptionCategory =
    | 'economy'
    | 'progression'
    | 'pacing'
    | 'player_behaviour'
    | 'complexity'
    | 'interaction'
    | 'other';

/**
 * How much the studio actually believes an assumption. The field that keeps the record honest.
 */
export type AssumptionConfidence = 'low' | 'medium' | 'high';

export type ObservationSeverity =
    'info' | 'low' | 'medium' | 'high' | 'critical';

/**
 * Where a balance observation came from. `playtest` and `session` name evidence that lives in another
 * bounded context, and the reference beside them is a plain string — this module never holds a copy of it.
 */
export type ObservationSourceType =
    'playtest' | 'session' | 'calculation' | 'simulation' | 'review' | 'other';

/**
 * How seriously to take something the analysis found. An error is a shape that cannot be a working economy
 * under any reading; a warning is one a designer might have meant.
 */
export type BalanceWarningSeverity = 'info' | 'warning' | 'error';

/**
 * What kind of record a finding is about, so the interface knows which list to link into.
 */
export type BalanceEntityType =
    | 'profile'
    | 'resource'
    | 'flow'
    | 'action'
    | 'cost'
    | 'reward'
    | 'effect'
    | 'variable';

export type SnapshotChangeType = 'added' | 'removed' | 'changed';

/**
 * One lifecycle move a record can currently make, already worded.
 */
export type Transition<TStatus> = {
    status: TStatus;
    label: string;
};

/**
 * An option offered by a picker, worded by the server.
 */
export type VocabularyOption<TValue extends string> = {
    value: TValue;
    label: string;
};

export type DescribedOption<TValue extends string> =
    VocabularyOption<TValue> & {
        description: string;
    };

/**
 * What the signed in account may do with a balance configuration.
 *
 * `canConfigure` is the one nearly every control on these screens is gated on: "may the configuration inside
 * this profile be changed?" is a single question with a single answer, and giving the interface one boolean
 * rather than one per kind of record is what stops the two from drifting apart control by control.
 *
 * `canCreateSnapshot` is deliberately looser than the rest — an archived profile refuses configuration and
 * still permits a snapshot, because "keep a copy of what we shipped" is a reason to take one.
 */
export type BalanceProfilePermissions = {
    canView: boolean;
    canUpdate: boolean;
    canActivate: boolean;
    canArchive: boolean;
    canConfigure: boolean;
    canCreateSnapshot: boolean;
};

export type BalanceProfile = {
    id: string;
    game_version_id: string;
    name: string;
    description: string | null;
    status: BalanceProfileStatus;
    status_label: string;
    is_active: boolean;
    created_by: string;
    creator?: User;
    version?: GameVersion;
    resources_count?: number;
    flows_count?: number;
    actions_count?: number;
    variables_count?: number;
    scenarios_count?: number;
    snapshots_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: BalanceProfilePermissions;
    available_transitions: Transition<BalanceProfileStatus>[];
};

/**
 * One thing players hold, gain and spend.
 *
 * The bounds are `string | null`, and null means unbounded rather than zero — a resource with no ceiling is
 * a shape the analysis reports on, and rendering it as "0" would invent a limit nobody set.
 */
export type ResourceType = {
    id: string;
    balance_profile_id: string;
    name: string;
    slug: string;
    category: ResourceCategory;
    category_label: string;
    description: string | null;
    unit: string | null;
    is_tradeable: boolean;
    is_accumulative: boolean;
    is_spendable: boolean;
    is_convertible: boolean;
    min_value: string | null;
    max_value: string | null;
    starting_value: string | null;
    position: number;
    flows_count?: number;
    costs_count?: number;
    rewards_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * One declared way a resource moves.
 *
 * `amount` is the magnitude the designer typed; `signed_amount` is what it does to the total in play. Both
 * travel, because the editor needs the first and the direction arrow needs the second.
 */
export type ResourceFlow = {
    id: string;
    balance_profile_id: string;
    resource_type_id: string;
    resource_name?: string | null;
    resource_slug?: string | null;
    name: string;
    description: string | null;
    flow_type: ResourceFlowType;
    flow_type_label: string;
    direction: number;
    amount: string;
    signed_amount: string;
    condition: string | null;
    position: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * One resource an action takes, or pays out.
 *
 * The two are the same shape because the *data* is the same shape; they differ in what they mean, which is
 * why they arrive in separate lists.
 */
export type ActionLine = {
    id: string;
    action_id: string;
    resource_type_id: string;
    resource_name: string | null;
    resource_slug: string | null;
    unit: string | null;
    amount: string;
    is_variable: boolean;
    min_amount: string | null;
    max_amount: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type ActionEffect = {
    id: string;
    action_id: string;
    effect_type: ActionEffectType;
    effect_type_label: string;
    expects_value: boolean;
    target: string;
    value: string | null;
    label: string;
    description: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type EconomyAction = {
    id: string;
    balance_profile_id: string;
    name: string;
    slug: string;
    description: string | null;
    position: number;
    costs?: ActionLine[];
    rewards?: ActionLine[];
    effects?: ActionEffect[];
    costs_count?: number;
    rewards_count?: number;
    effects_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * One number a designer tunes.
 *
 * `is_within_range` comes from the server rather than being computed here, so the table's warning marker and
 * the analysis agree by construction — and so nothing has to parse a decimal string into a float to compare
 * two of them.
 */
export type BalanceVariable = {
    id: string;
    balance_profile_id: string;
    resource_type_id: string | null;
    resource_name: string | null;
    action_id: string | null;
    action_name: string | null;
    name: string;
    slug: string;
    description: string | null;
    value: string;
    unit: string | null;
    min_value: string | null;
    max_value: string | null;
    step: string | null;
    category: BalanceVariableCategory;
    category_label: string;
    is_probability: boolean;
    is_within_range: boolean;
    overrides_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * One value a scenario states differently.
 *
 * The base value travels beside the override, because "15" is only a scenario when you can see that the
 * profile says 10.
 */
export type ScenarioVariable = {
    id: string;
    scenario_id: string;
    balance_variable_id: string;
    variable_name: string | null;
    variable_slug: string | null;
    unit: string | null;
    base_value: string | null;
    value: string;
    delta: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type BalanceScenario = {
    id: string;
    balance_profile_id: string;
    name: string;
    description: string | null;
    status: BalanceScenarioStatus;
    status_label: string;
    is_modifiable: boolean;
    created_by: string;
    creator?: User;
    overrides?: ScenarioVariable[];
    overrides_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

export type BalanceAssumption = {
    id: string;
    balance_profile_id: string;
    title: string;
    description: string | null;
    category: AssumptionCategory;
    category_label: string;
    confidence: AssumptionConfidence;
    confidence_label: string;
    needs_evidence: boolean;
    created_by: string;
    creator?: User;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * What the studio noticed about the economy.
 *
 * `source_reference` is a plain string this module does not resolve, so it is rendered as text rather than
 * as a link — an interface that linked it would be promising something no endpoint here can deliver.
 */
export type BalanceObservation = {
    id: string;
    balance_profile_id: string;
    title: string;
    observation: string;
    source_type: ObservationSourceType;
    source_type_label: string;
    source_reference: string | null;
    is_empirical: boolean;
    severity: ObservationSeverity;
    severity_label: string;
    demands_action: boolean;
    created_by: string;
    creator?: User;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * One frozen configuration.
 *
 * The payload itself is absent on purpose — a real snapshot runs to hundreds of kilobytes and the list draws
 * four at once. Reading the contents is what the comparison endpoint is for.
 */
export type BalanceSnapshot = {
    id: string;
    balance_profile_id: string;
    name: string;
    description: string | null;
    tally: Record<string, number>;
    created_by: string;
    creator?: User;
    created_at: string | null;
};

/**
 * One thing the analysis noticed.
 *
 * `code` travels as well as the wording, and that is the field to key on: wording is translated and will
 * change, the code will not.
 */
export type BalanceWarning = {
    code: string;
    severity: BalanceWarningSeverity;
    severity_label: string;
    is_error: boolean;
    title: string;
    description: string;
    explanation: string;
    subject: string;
    entity_type: BalanceEntityType;
    entity_type_label: string;
    entity_id: string | null;
};

/**
 * What enters and leaves for one resource.
 *
 * All three figures travel, because 12-in-8-out and 2-in-0-out both net +4 and are completely different
 * games.
 */
export type ResourceNetFlow = {
    resource_id: string;
    resource_name: string;
    generation: string;
    consumption: string;
    net: string;
    has_generation: boolean;
    has_consumption: boolean;
    is_surplus: boolean;
    is_deficit: boolean;
    is_balanced: boolean;
};

export type ResourceDelta = {
    resource_id: string;
    resource_name: string;
    unit: string | null;
    cost: string;
    reward: string;
    net: string;
    is_gain: boolean;
    is_spend: boolean;
};

/**
 * What an action does to a player's holdings.
 *
 * One line per resource, and no total — there is no field here a total could live in, because working one
 * out would require deciding that wood and gold are interchangeable.
 */
export type ActionProfitability = {
    action_id: string;
    action_name: string;
    deltas: ResourceDelta[];
    effect_count: number;
    has_cost: boolean;
    has_reward: boolean;
    has_outcome: boolean;
    multiplied_resources: ResourceDelta[];
};

/**
 * What one resource buys of another, through a particular action.
 *
 * A null ratio is a real answer rather than a failure: an action that costs nothing converts nothing at any
 * rate.
 */
export type ConversionRatio = {
    action_id: string;
    action_name: string;
    from_resource_id: string;
    from_resource_name: string;
    from_amount: string;
    to_resource_id: string;
    to_resource_name: string;
    to_amount: string;
    ratio: string | null;
    is_defined: boolean;
    label: string;
};

export type BalanceSummary = {
    resources: number;
    flows: number;
    actions: number;
    costs: number;
    rewards: number;
    effects: number;
    variables: number;
    scenarios: number;
    assumptions: number;
    observations: number;
    warnings: number;
    errors: number;
    is_empty: boolean;
    has_errors: boolean;
    has_findings: boolean;
};

/**
 * A complete reading of a configuration, as of the moment it was requested.
 *
 * Nothing here is stored. The configuration travels with the findings so that a warning and the resource it
 * concerns are drawn from the same moment.
 *
 * The lists here are plain arrays rather than `{ data: [] }`, unlike a top-level Inertia prop. An API
 * Resource only wraps itself in `data` when it is the root of a response — nested inside another resource it
 * serialises bare — so this shape follows what the server actually sends rather than what the outer props
 * look like.
 */
export type BalanceAnalysis = {
    profile: BalanceProfile;
    resources: ResourceType[];
    flows: ResourceFlow[];
    actions: EconomyAction[];
    variables: BalanceVariable[];
    net_flows: ResourceNetFlow[];
    profitability: ActionProfitability[];
    conversions: ConversionRatio[];
    warnings: BalanceWarning[];
    errors: BalanceWarning[];
    advisories: BalanceWarning[];
    summary: BalanceSummary;
};

export type FieldChange = {
    field: string;
    label: string;
    before: string | null;
    after: string | null;
};

export type SnapshotChange = {
    type: SnapshotChangeType;
    type_label: string;
    entity_type: BalanceEntityType;
    entity_type_label: string;
    key: string;
    label: string;
    fields: FieldChange[];
};

/**
 * The difference between two frozen configurations, earlier first.
 *
 * Direction is fixed, so "10 → 12" can be read at face value without checking which way round the request
 * was made.
 */
export type BalanceComparison = {
    from: { id: string; name: string };
    to: { id: string; name: string };
    resources: SnapshotChange[];
    flows: SnapshotChange[];
    actions: SnapshotChange[];
    costs: SnapshotChange[];
    rewards: SnapshotChange[];
    effects: SnapshotChange[];
    variables: SnapshotChange[];
    count: number;
    is_identical: boolean;
};

/**
 * The vocabulary the balance screens choose from, worded by the server.
 *
 * The extra booleans travel with the options that need them — `expects_value` on an effect type,
 * `expects_reference` on an observation source, `direction` on a flow type — so a form can decide whether to
 * show a field without holding a second copy of which cases imply what.
 */
export type BalanceOptions = {
    profile_statuses: VocabularyOption<BalanceProfileStatus>[];
    resource_categories: DescribedOption<ResourceCategory>[];
    flow_types: (DescribedOption<ResourceFlowType> & { direction: number })[];
    effect_types: (DescribedOption<ActionEffectType> & {
        expects_value: boolean;
    })[];
    variable_categories: DescribedOption<BalanceVariableCategory>[];
    scenario_statuses: VocabularyOption<BalanceScenarioStatus>[];
    assumption_categories: DescribedOption<AssumptionCategory>[];
    confidences: DescribedOption<AssumptionConfidence>[];
    observation_severities: DescribedOption<ObservationSeverity>[];
    observation_sources: (DescribedOption<ObservationSourceType> & {
        expects_reference: boolean;
    })[];
};
