/**
 * The design record form's input shape and its local checks.
 *
 * Numbers are held as strings while the form is open. That is not laziness: an
 * empty number input gives `''`, and coercing it eagerly turns "the designer has
 * not decided" into `0` or `NaN` — both of which would be recorded as decisions.
 * The strings are converted once, on submit, and an empty one becomes null.
 *
 * The bounds mirror `PlayerCountRange` and `PlayTimeRange`. The server is the
 * authority; these exist so a mistake is caught before a round trip.
 */

import type { Complexity } from '../types/design-record';

export const PLAYER_COUNT_MIN = 1;
export const PLAYER_COUNT_MAX = 99;

export const PLAY_TIME_MIN = 1;
export const PLAY_TIME_MAX = 1440;

export const TARGET_AGE_MIN = 1;
export const TARGET_AGE_MAX = 99;

export const PITCH_MAX_LENGTH = 300;
export const AUDIENCE_MAX_LENGTH = 300;
export const CORE_LOOP_MAX_LENGTH = 2000;

/**
 * The maximum number of mechanics a design can claim.
 *
 * Not a technical limit. A game described by thirty mechanics has not been
 * described, and the ceiling is there to say so.
 */
export const MECHANICS_MAX = 30;

export type DesignRecordInput = {
    pitch: string;
    player_count_min: string;
    player_count_max: string;
    play_time_min: string;
    play_time_max: string;
    target_age_min: string;
    complexity: Complexity | '';
    audience: string;
    core_action: string;
    core_cost: string;
    core_reward: string;
    win_condition: string;
    failure_condition: string;
    mechanics: string[];
};

/**
 * An untouched form.
 */
export function emptyDesignRecordInput(): DesignRecordInput {
    return {
        pitch: '',
        player_count_min: '',
        player_count_max: '',
        play_time_min: '',
        play_time_max: '',
        target_age_min: '',
        complexity: '',
        audience: '',
        core_action: '',
        core_cost: '',
        core_reward: '',
        win_condition: '',
        failure_condition: '',
        mechanics: [],
    };
}

/**
 * Turn a form value into the number the server expects, or nothing.
 *
 * An empty box is undecided. Anything unparseable is also undecided rather than
 * zero, because a design has never been for `NaN` players.
 */
export function toOptionalNumber(value: string): number | null {
    const trimmed = value.trim();

    if (trimmed === '') {
        return null;
    }

    const parsed = Number(trimmed);

    return Number.isInteger(parsed) ? parsed : null;
}

/**
 * Check one end of a numeric range.
 */
function validateBound(
    value: string,
    min: number,
    max: number,
    noun: string,
): string | undefined {
    const parsed = toOptionalNumber(value);

    if (value.trim() !== '' && parsed === null) {
        return `${noun} has to be a whole number.`;
    }

    if (parsed === null) {
        return undefined;
    }

    if (parsed < min) {
        return `${noun} cannot be below ${min}.`;
    }

    if (parsed > max) {
        return `${noun} cannot be above ${max}.`;
    }

    return undefined;
}

/**
 * Check that a range does not run backwards.
 *
 * The same rule `PlayerCountRange` and `PlayTimeRange` enforce. Checked here as
 * well because "4 to 2 players" is almost always two boxes filled in the wrong
 * order, and telling somebody before they submit is kinder than after.
 */
function validateOrder(
    min: string,
    max: string,
    message: string,
): string | undefined {
    const from = toOptionalNumber(min);
    const to = toOptionalNumber(max);

    if (from === null || to === null) {
        return undefined;
    }

    return to < from ? message : undefined;
}

export function validateDesignRecord(
    input: DesignRecordInput,
): Partial<Record<keyof DesignRecordInput, string>> {
    const errors: Partial<Record<keyof DesignRecordInput, string>> = {};

    const playerCountMin =
        validateBound(
            input.player_count_min,
            PLAYER_COUNT_MIN,
            PLAYER_COUNT_MAX,
            'A player count',
        ) ??
        validateOrder(
            input.player_count_min,
            input.player_count_max,
            'The upper player count cannot be below the lower one.',
        );

    if (playerCountMin) {
        errors.player_count_min = playerCountMin;
    }

    const playerCountMax = validateBound(
        input.player_count_max,
        PLAYER_COUNT_MIN,
        PLAYER_COUNT_MAX,
        'A player count',
    );

    if (playerCountMax) {
        errors.player_count_max = playerCountMax;
    }

    const playTimeMin =
        validateBound(
            input.play_time_min,
            PLAY_TIME_MIN,
            PLAY_TIME_MAX,
            'A playing time',
        ) ??
        validateOrder(
            input.play_time_min,
            input.play_time_max,
            'The longer playing time cannot be below the shorter one.',
        );

    if (playTimeMin) {
        errors.play_time_min = playTimeMin;
    }

    const playTimeMax = validateBound(
        input.play_time_max,
        PLAY_TIME_MIN,
        PLAY_TIME_MAX,
        'A playing time',
    );

    if (playTimeMax) {
        errors.play_time_max = playTimeMax;
    }

    const age = validateBound(
        input.target_age_min,
        TARGET_AGE_MIN,
        TARGET_AGE_MAX,
        'A target age',
    );

    if (age) {
        errors.target_age_min = age;
    }

    if (input.pitch.length > PITCH_MAX_LENGTH) {
        errors.pitch = `A pitch of one sentence fits in ${PITCH_MAX_LENGTH} characters.`;
    }

    if (input.audience.length > AUDIENCE_MAX_LENGTH) {
        errors.audience = `Keep this under ${AUDIENCE_MAX_LENGTH} characters.`;
    }

    if (input.mechanics.length > MECHANICS_MAX) {
        errors.mechanics = `A design described by more than ${MECHANICS_MAX} mechanics has not been described.`;
    }

    return errors;
}
