/**
 * Client-side shapes and checks for rules forms.
 *
 * These mirror `Modules\GameRules\Application\Validation\RuleValidationRules` and the form requests beside
 * it. They exist to give immediate feedback while somebody types; the server validates every field again
 * and its answer wins.
 *
 * The numbers are duplicated rather than fetched, which is a deliberate small cost: a limit that arrives
 * over the wire cannot be checked before the first keystroke, and being told "too long" after submitting is
 * exactly the experience these checks exist to avoid.
 *
 * ## What is deliberately not checked here
 *
 * A condition's value against its operator. "Is at least blue" is a *finding*, not a refusal — somebody
 * halfway through typing a sentence should not be stopped by it, and the validator says so on the screen
 * where they can act on it. The same goes for an economy handle that names nothing.
 */

import type {
    ConditionOperator,
    ConditionType,
    EffectType,
    GamePhaseType,
    LogicOperator,
    MechanicCategory,
    ReferenceType,
    RequirementType,
    RuleActionType,
    RuleStatus,
    RuleType,
    TriggerType,
} from '../types/game-rules';

export const RULE_SET_NAME_MIN_LENGTH = 2;
export const RULE_SET_NAME_MAX_LENGTH = 160;
export const DESCRIPTION_MAX_LENGTH = 10000;
export const SHORT_DESCRIPTION_MAX_LENGTH = 2000;

export const NAME_MIN_LENGTH = 1;
export const NAME_MAX_LENGTH = 120;

/**
 * Conditions, groups, triggers and the three outcomes are referred to in prose, so a one-character name
 * reads worse than none at all.
 */
export const STATEMENT_NAME_MIN_LENGTH = 2;
export const STATEMENT_NAME_MAX_LENGTH = 160;

export const REQUIREMENT_DESCRIPTION_MIN_LENGTH = 3;
export const REQUIREMENT_DESCRIPTION_MAX_LENGTH = 2000;

export const EFFECT_TARGET_MIN_LENGTH = 1;
export const EFFECT_TARGET_MAX_LENGTH = 160;

export const VALUE_MAX_LENGTH = 255;
export const ECONOMY_HANDLE_MAX_LENGTH = 80;

export type RuleSetInput = {
    name: string;
    description: string;
};

export type RuleInput = {
    name: string;
    description: string;
    parent_rule_id: string;
    phase_id: string;
    rule_type: RuleType;
    status: RuleStatus;
};

export type MechanicInput = {
    name: string;
    description: string;
    category: MechanicCategory;
};

export type PhaseInput = {
    name: string;
    description: string;
    parent_phase_id: string;
    phase_type: GamePhaseType;
    status: RuleStatus;
};

export type TransitionInput = {
    from_phase_id: string;
    to_phase_id: string;
    condition_id: string;
    trigger_id: string;
};

export type ActionInput = {
    name: string;
    description: string;
    phase_id: string;
    action_type: RuleActionType;
    status: RuleStatus;
    economy_action_slug: string;
};

export type RequirementInput = {
    rule_id: string;
    action_id: string;
    requirement_type: RequirementType;
    description: string;
    value: string;
    economy_resource_slug: string;
};

export type EffectInput = {
    rule_id: string;
    action_id: string;
    effect_type: EffectType;
    target: string;
    value: string;
    description: string;
    economy_resource_slug: string;
};

export type ConditionInput = {
    name: string;
    description: string;
    condition_type: ConditionType;
    operator: ConditionOperator;
    value: string;
};

export type ConditionGroupInput = {
    name: string;
    description: string;
    logic_operator: LogicOperator;
};

export type TriggerInput = {
    name: string;
    description: string;
    trigger_type: TriggerType;
};

export type OutcomeInput = {
    name: string;
    description: string;
    condition_id: string;
};

export type ReferenceInput = {
    referenced_rule_id: string;
    reference_type: ReferenceType;
    description: string;
};

export const emptyRuleSetInput: RuleSetInput = { name: '', description: '' };

export const emptyRuleInput: RuleInput = {
    name: '',
    description: '',
    parent_rule_id: '',
    phase_id: '',
    rule_type: 'general',
    status: 'active',
};

export const emptyMechanicInput: MechanicInput = {
    name: '',
    description: '',
    category: 'other',
};

export const emptyPhaseInput: PhaseInput = {
    name: '',
    description: '',
    parent_phase_id: '',
    phase_type: 'round',
    status: 'active',
};

export const emptyTransitionInput: TransitionInput = {
    from_phase_id: '',
    to_phase_id: '',
    condition_id: '',
    trigger_id: '',
};

export const emptyActionInput: ActionInput = {
    name: '',
    description: '',
    phase_id: '',
    action_type: 'basic',
    status: 'active',
    economy_action_slug: '',
};

export const emptyRequirementInput: RequirementInput = {
    rule_id: '',
    action_id: '',
    requirement_type: 'custom',
    description: '',
    value: '',
    economy_resource_slug: '',
};

export const emptyEffectInput: EffectInput = {
    rule_id: '',
    action_id: '',
    effect_type: 'custom',
    target: '',
    value: '',
    description: '',
    economy_resource_slug: '',
};

export const emptyConditionInput: ConditionInput = {
    name: '',
    description: '',
    condition_type: 'custom',
    operator: 'equals',
    value: '',
};

export const emptyConditionGroupInput: ConditionGroupInput = {
    name: '',
    description: '',
    logic_operator: 'and',
};

export const emptyTriggerInput: TriggerInput = {
    name: '',
    description: '',
    trigger_type: 'custom',
};

export const emptyOutcomeInput: OutcomeInput = {
    name: '',
    description: '',
    condition_id: '',
};

export const emptyReferenceInput: ReferenceInput = {
    referenced_rule_id: '',
    reference_type: 'related_to',
    description: '',
};

type LengthCheck = {
    min?: number;
    max: number;
    tooShort: string;
    tooLong: string;
};

/**
 * Check a field's length, and return the message to show or nothing.
 *
 * Trims first, so a name of three spaces is empty rather than three characters long — which is the same
 * reading the server takes.
 */
export function validateLength(
    value: string,
    { min = 0, max, tooShort, tooLong }: LengthCheck,
): string | null {
    const trimmed = value.trim();

    if (trimmed.length < min) {
        return tooShort;
    }

    if (trimmed.length > max) {
        return tooLong;
    }

    return null;
}
