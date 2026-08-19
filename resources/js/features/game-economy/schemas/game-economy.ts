/**
 * Client-side shapes and checks for balance forms.
 *
 * These mirror `Modules\GameEconomy\Application\Validation\EconomyValidationRules` and the form requests
 * beside it. They exist to give immediate feedback while somebody types; the server validates every field
 * again and its answer wins.
 *
 * The numbers are duplicated rather than fetched, which is a deliberate small cost: a limit that arrives
 * over the wire cannot be checked before the first keystroke, and being told "too long" after submitting is
 * exactly the experience these checks exist to avoid.
 *
 * ## Amounts are strings
 *
 * Every numeric field in this module is a string on the way in and on the way out. `isValidAmount` checks
 * the shape without parsing, because parsing is the thing that loses precision — a designer typing 0.1 must
 * get 0.1 back, and `Number('0.1')` is where that stops being true.
 */

import type {
    ActionEffectType,
    AssumptionCategory,
    AssumptionConfidence,
    BalanceVariableCategory,
    ObservationSeverity,
    ObservationSourceType,
    ResourceCategory,
    ResourceFlowType,
} from '../types/game-economy';

export const PROFILE_NAME_MIN_LENGTH = 2;
export const PROFILE_NAME_MAX_LENGTH = 160;
export const DESCRIPTION_MAX_LENGTH = 5000;

export const RESOURCE_NAME_MIN_LENGTH = 1;
export const RESOURCE_NAME_MAX_LENGTH = 120;
export const UNIT_MAX_LENGTH = 40;

export const FLOW_NAME_MIN_LENGTH = 2;
export const FLOW_NAME_MAX_LENGTH = 160;
export const CONDITION_MAX_LENGTH = 500;

export const ACTION_NAME_MIN_LENGTH = 1;
export const ACTION_NAME_MAX_LENGTH = 120;

export const EFFECT_TARGET_MIN_LENGTH = 1;
export const EFFECT_TARGET_MAX_LENGTH = 160;

export const VARIABLE_NAME_MIN_LENGTH = 1;
export const VARIABLE_NAME_MAX_LENGTH = 120;

export const SCENARIO_NAME_MIN_LENGTH = 2;
export const SCENARIO_NAME_MAX_LENGTH = 120;

/**
 * "Food matters" is not a belief anybody can test, and an assumption nobody can test is a note.
 */
export const ASSUMPTION_TITLE_MIN_LENGTH = 10;
export const ASSUMPTION_TITLE_MAX_LENGTH = 200;

export const OBSERVATION_TITLE_MIN_LENGTH = 3;
export const OBSERVATION_TITLE_MAX_LENGTH = 200;

/**
 * A severity with no account of what was seen is an alarm without a reason.
 */
export const OBSERVATION_BODY_MIN_LENGTH = 10;
export const OBSERVATION_BODY_MAX_LENGTH = 5000;

export const SOURCE_REFERENCE_MAX_LENGTH = 200;

export const SNAPSHOT_NAME_MIN_LENGTH = 1;
export const SNAPSHOT_NAME_MAX_LENGTH = 80;

/**
 * The number of decimal places every amount carries, matching the `decimal(20, 6)` columns.
 */
export const AMOUNT_SCALE = 6;

export type CreateProfileInput = {
    name: string;
    description: string;
};

export type ResourceInput = {
    name: string;
    description: string;
    unit: string;
    category: ResourceCategory;
    is_tradeable: boolean;
    is_accumulative: boolean;
    is_spendable: boolean;
    is_convertible: boolean;
    min_value: string;
    max_value: string;
    starting_value: string;
};

export type FlowInput = {
    resource_type_id: string;
    name: string;
    description: string;
    flow_type: ResourceFlowType;
    amount: string;
    condition: string;
};

export type ActionInput = {
    name: string;
    description: string;
};

export type ActionLineInput = {
    resource_type_id: string;
    amount: string;
    is_variable: boolean;
    min_amount: string;
    max_amount: string;
};

export type EffectInput = {
    effect_type: ActionEffectType;
    target: string;
    value: string;
    description: string;
};

export type VariableInput = {
    name: string;
    description: string;
    value: string;
    unit: string;
    min_value: string;
    max_value: string;
    step: string;
    category: BalanceVariableCategory;
    resource_type_id: string;
    action_id: string;
};

export type ScenarioInput = {
    name: string;
    description: string;
};

export type ScenarioVariableInput = {
    balance_variable_id: string;
    value: string;
};

export type AssumptionInput = {
    title: string;
    description: string;
    category: AssumptionCategory;
    confidence: AssumptionConfidence;
};

export type ObservationInput = {
    title: string;
    observation: string;
    source_type: ObservationSourceType;
    source_reference: string;
    severity: ObservationSeverity;
};

export type SnapshotInput = {
    name: string;
    description: string;
};

export const emptyProfileInput: CreateProfileInput = {
    name: '',
    description: '',
};

export const emptyResourceInput: ResourceInput = {
    name: '',
    description: '',
    unit: '',
    category: 'material',
    is_tradeable: true,
    is_accumulative: true,
    is_spendable: true,
    is_convertible: false,
    min_value: '',
    max_value: '',
    starting_value: '',
};

export const emptyFlowInput: FlowInput = {
    resource_type_id: '',
    name: '',
    description: '',
    flow_type: 'generation',
    amount: '',
    condition: '',
};

export const emptyActionInput: ActionInput = {
    name: '',
    description: '',
};

export const emptyActionLineInput: ActionLineInput = {
    resource_type_id: '',
    amount: '',
    is_variable: false,
    min_amount: '',
    max_amount: '',
};

export const emptyEffectInput: EffectInput = {
    effect_type: 'unlock',
    target: '',
    value: '',
    description: '',
};

export const emptyVariableInput: VariableInput = {
    name: '',
    description: '',
    value: '',
    unit: '',
    min_value: '',
    max_value: '',
    step: '',
    category: 'other',
    resource_type_id: '',
    action_id: '',
};

export const emptyScenarioInput: ScenarioInput = {
    name: '',
    description: '',
};

export const emptyAssumptionInput: AssumptionInput = {
    title: '',
    description: '',
    category: 'economy',
    confidence: 'medium',
};

export const emptyObservationInput: ObservationInput = {
    title: '',
    observation: '',
    source_type: 'playtest',
    source_reference: '',
    severity: 'medium',
};

export const emptySnapshotInput: SnapshotInput = {
    name: '',
    description: '',
};

/**
 * Determine whether a string is shaped like an amount this module would accept.
 *
 * Deliberately a pattern rather than a parse. `Number('0.1')` is where exactness stops being true, and this
 * check exists on a screen whose entire purpose is exact numbers — so it looks at the characters and says
 * nothing about the value.
 *
 * Scientific notation is refused for the same reason the server refuses it: `1e3` is not something a
 * designer types into a cost field, and accepting it would mean the two ends disagreed about what a number
 * is.
 */
export function isValidAmount(value: string): boolean {
    return /^-?\d+(\.\d+)?$/.test(value.trim());
}

/**
 * Determine whether an amount has more precision than the columns keep.
 *
 * Refused rather than rounded, because silently truncating a designer's number is worse than telling them.
 */
export function hasTooMuchPrecision(value: string): boolean {
    const [, fraction = ''] = value.trim().split('.');

    return fraction.length > AMOUNT_SCALE;
}

type AmountCheck = {
    required?: boolean;
    allowNegative?: boolean;
    missing: string;
    malformed: string;
    negative?: string;
    tooPrecise: string;
};

/**
 * Check one amount field, returning a message when it is wrong.
 *
 * `null` means the field is fine, which is the shape `useBalanceForm` expects — an entry whose value is
 * undefined is not an error.
 */
export function validateAmount(
    value: string,
    checks: AmountCheck,
): string | null {
    const trimmed = value.trim();

    if (trimmed === '') {
        return checks.required ? checks.missing : null;
    }

    if (!isValidAmount(trimmed)) {
        return checks.malformed;
    }

    if (hasTooMuchPrecision(trimmed)) {
        return checks.tooPrecise;
    }

    if (
        checks.allowNegative === false &&
        trimmed.startsWith('-') &&
        Number.parseInt(trimmed.replace(/[-.]/g, ''), 10) !== 0
    ) {
        return checks.negative ?? checks.malformed;
    }

    return null;
}

type LengthCheck = {
    min?: number;
    max: number;
    tooShort: string;
    tooLong: string;
};

/**
 * Check one text field against the same limits the server uses.
 */
export function validateLength(
    value: string,
    { min = 0, max, tooShort, tooLong }: LengthCheck,
): string | null {
    const trimmed = value.trim();

    if (trimmed.length < min) {
        return tooShort;
    }

    return trimmed.length > max ? tooLong : null;
}
