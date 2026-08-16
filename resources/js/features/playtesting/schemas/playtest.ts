/**
 * Client-side shapes and checks for playtesting forms.
 *
 * These mirror `Modules\Playtesting\Application\Validation\PlaytestValidationRules`
 * and the form requests beside it. They exist to give immediate feedback while
 * somebody types; the server validates every field again and its answer wins.
 *
 * The numbers are duplicated rather than fetched, which is a deliberate small
 * cost: a limit that arrives over the wire cannot be checked before the first
 * keystroke, and being told "too long" after submitting is exactly the
 * experience these checks exist to avoid.
 */

import type {
    ObservationCategory,
    ParticipantRole,
    PlaytestStatus,
} from '../types/playtest';

export const PLAYTEST_TITLE_MIN_LENGTH = 3;
export const PLAYTEST_TITLE_MAX_LENGTH = 160;

/**
 * Longer than a title's floor on purpose. "Test the game" is not an objective,
 * and a playtest whose purpose nobody wrote down is one nobody can interpret
 * six months later.
 */
export const PLAYTEST_OBJECTIVE_MIN_LENGTH = 10;
export const PLAYTEST_OBJECTIVE_MAX_LENGTH = 2000;
export const PLAYTEST_HYPOTHESIS_MAX_LENGTH = 2000;
export const PLAYTEST_CONCLUSION_MAX_LENGTH = 5000;

export const SESSION_LOCATION_MAX_LENGTH = 160;
export const SESSION_NOTES_MAX_LENGTH = 10000;
export const SESSION_OUTCOME_MAX_LENGTH = 5000;

export const DISPLAY_NAME_MAX_LENGTH = 120;

/**
 * The shortest useful observation is a few words — "market ignored" is a real
 * note somebody types mid-turn — so the floor is deliberately low.
 */
export const OBSERVATION_MIN_LENGTH = 3;
export const OBSERVATION_MAX_LENGTH = 5000;

export const FEEDBACK_MIN_LENGTH = 3;
export const FEEDBACK_MAX_LENGTH = 5000;

export const RATING_MIN = 1;
export const RATING_MAX = 5;

export type CreatePlaytestInput = {
    game_version_id: string;
    title: string;
    objective: string;
    hypothesis: string;
    planned_at: string;
};

export type UpdatePlaytestInput = {
    game_version_id: string;
    title: string;
    objective: string;
    hypothesis: string;
    planned_at: string;
};

export type CompletePlaytestInput = {
    conclusion: string;
};

export type CreateSessionInput = {
    planned_at: string;
    location: string;
    notes: string;
};

export type CompleteSessionInput = {
    outcome: string;
    notes: string;
};

export type AddParticipantInput = {
    display_name: string;
    role: ParticipantRole;
    user_id: string;
};

export type CreateObservationInput = {
    content: string;
    category: ObservationCategory;
    participant_id: string;
    observed_at: string;
};

export type CreateFeedbackInput = {
    content: string;
    participant_id: string;

    /**
     * A string because it comes from a form control, and because "" has to
     * stay distinguishable from "1" — an empty rating means the participant
     * did not put a number on their comment, not that they scored it lowest.
     */
    rating: string;
};

/**
 * A new playtest has no version chosen and no date; the screen fills the
 * version in from the game's latest, which is what is usually being tested.
 */
export const emptyCreatePlaytestInput: CreatePlaytestInput = {
    game_version_id: '',
    title: '',
    objective: '',
    hypothesis: '',
    planned_at: '',
};

export const emptyCreateSessionInput: CreateSessionInput = {
    planned_at: '',
    location: '',
    notes: '',
};

export const emptyCompleteSessionInput: CompleteSessionInput = {
    outcome: '',
    notes: '',
};

/**
 * Player, because most people at a playtest are playing and the live screen
 * has to be quick above all else.
 */
export const emptyAddParticipantInput: AddParticipantInput = {
    display_name: '',
    role: 'player',
    user_id: '',
};

export const emptyCreateObservationInput: CreateObservationInput = {
    content: '',
    category: 'other',
    participant_id: '',
    observed_at: '',
};

export const emptyCreateFeedbackInput: CreateFeedbackInput = {
    content: '',
    participant_id: '',
    rating: '',
};

/**
 * Explain why a playtest title is unusable, or return null when it is fine.
 */
export function validatePlaytestTitle(title: string): string | null {
    const trimmed = title.trim();

    if (trimmed.length < PLAYTEST_TITLE_MIN_LENGTH) {
        return `The title must be at least ${PLAYTEST_TITLE_MIN_LENGTH} characters long.`;
    }

    if (trimmed.length > PLAYTEST_TITLE_MAX_LENGTH) {
        return `The title may not be longer than ${PLAYTEST_TITLE_MAX_LENGTH} characters.`;
    }

    return null;
}

/**
 * Explain why an objective is unusable, or return null when it is fine.
 *
 * The floor is the only opinionated check in this file. It exists because a
 * playtest is read months later by somebody trying to work out what was being
 * asked, and "test it" tells them nothing.
 */
export function validatePlaytestObjective(objective: string): string | null {
    const trimmed = objective.trim();

    if (trimmed.length < PLAYTEST_OBJECTIVE_MIN_LENGTH) {
        return 'Say what this playtest is meant to find out.';
    }

    if (trimmed.length > PLAYTEST_OBJECTIVE_MAX_LENGTH) {
        return `The objective may not be longer than ${PLAYTEST_OBJECTIVE_MAX_LENGTH} characters.`;
    }

    return null;
}

/**
 * Explain why an observation is unusable, or return null when it is fine.
 */
export function validateObservation(content: string): string | null {
    const trimmed = content.trim();

    if (trimmed.length < OBSERVATION_MIN_LENGTH) {
        return 'Say what you noticed.';
    }

    if (trimmed.length > OBSERVATION_MAX_LENGTH) {
        return `An observation may not be longer than ${OBSERVATION_MAX_LENGTH} characters.`;
    }

    return null;
}

/**
 * Explain why a piece of feedback is unusable, or return null when it is fine.
 */
export function validateFeedback(content: string): string | null {
    const trimmed = content.trim();

    if (trimmed.length < FEEDBACK_MIN_LENGTH) {
        return 'Say what they told you.';
    }

    if (trimmed.length > FEEDBACK_MAX_LENGTH) {
        return `Feedback may not be longer than ${FEEDBACK_MAX_LENGTH} characters.`;
    }

    return null;
}

/**
 * Explain why a participant's name is unusable, or return null when it is
 * fine.
 *
 * Required for everybody, account or not: the name is what the session reads
 * back with, so somebody who introduced themselves as "Sam" stays Sam whatever
 * their profile says a year later.
 */
export function validateDisplayName(name: string): string | null {
    const trimmed = name.trim();

    if (trimmed.length === 0) {
        return 'Give this person a name for the session.';
    }

    if (trimmed.length > DISPLAY_NAME_MAX_LENGTH) {
        return `A name may not be longer than ${DISPLAY_NAME_MAX_LENGTH} characters.`;
    }

    return null;
}

/**
 * The statuses a playtests list can be filtered by, plus "everything".
 *
 * An empty string rather than a null, because that is what an unset `<Select>`
 * gives back and translating it once here beats translating it at every call
 * site.
 */
export type PlaytestStatusFilter = PlaytestStatus | '';
