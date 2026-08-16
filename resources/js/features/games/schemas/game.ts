/**
 * Client-side shapes and checks for game forms.
 *
 * These mirror `Modules\GameDesign\Domain\ValueObjects\GameSlug` and the form
 * requests beside it. They exist to give immediate feedback while somebody
 * types; the server validates every field again and its answer wins.
 */

import type { DesignPhase, GameStatus } from '../types/game';

export const GAME_NAME_MIN_LENGTH = 2;
export const GAME_NAME_MAX_LENGTH = 120;

/**
 * Shorter than a workspace address on purpose: "Go", "Uno" and "Hive" are
 * real games.
 */
export const GAME_SLUG_MIN_LENGTH = 2;
export const GAME_SLUG_MAX_LENGTH = 64;
export const GAME_DESCRIPTION_MAX_LENGTH = 2000;

export const VERSION_NAME_MAX_LENGTH = 120;
export const VERSION_DESCRIPTION_MAX_LENGTH = 5000;

/**
 * Addresses the game routes need for themselves.
 *
 * Kept in step with the `RESERVED` list on the value object.
 */
const RESERVED_SLUGS = new Set([
    'archive',
    'create',
    'design-phase',
    'edit',
    'new',
    'settings',
    'status',
    'version',
    'versions',
]);

const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export type CreateGameInput = {
    name: string;
    slug: string;
    description: string;
    design_phase: DesignPhase;
};

export type UpdateGameInput = {
    name: string;
    slug: string;
    description: string;
};

export type ChangeGameStatusInput = {
    status: GameStatus;
};

export type ChangeDesignPhaseInput = {
    design_phase: DesignPhase;
};

export type CreateGameVersionInput = {
    name: string;
    description: string;
};

/**
 * A new game is nobody's active project and nothing is designed yet.
 *
 * The status is not offered on the form at all — a game always starts as a
 * draft, and the server ignores anything sent for it.
 */
export const emptyCreateGameInput: CreateGameInput = {
    name: '',
    slug: '',
    description: '',
    design_phase: 'idea',
};

export const emptyCreateGameVersionInput: CreateGameVersionInput = {
    name: '',
    description: '',
};

/**
 * Suggest an address for a game name.
 *
 * Used to fill the address field as somebody types the name, so they can see
 * and override what their URL will be. The server derives its own when the
 * field is left empty, and resolves collisions there.
 */
export function suggestGameSlug(name: string): string {
    return name
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, GAME_SLUG_MAX_LENGTH)
        .replace(/-+$/, '');
}

/**
 * Explain why an address is unusable, or return null when it is fine.
 *
 * An empty value is treated as valid: leaving the field blank asks the server
 * to pick an address, which is a legitimate choice rather than an error.
 */
export function validateGameSlug(slug: string): string | null {
    if (slug === '') {
        return null;
    }

    if (slug.length < GAME_SLUG_MIN_LENGTH) {
        return `The address must be at least ${GAME_SLUG_MIN_LENGTH} characters long.`;
    }

    if (slug.length > GAME_SLUG_MAX_LENGTH) {
        return `The address may not be longer than ${GAME_SLUG_MAX_LENGTH} characters.`;
    }

    if (!SLUG_PATTERN.test(slug)) {
        return 'The address may only contain lowercase letters, numbers and single hyphens.';
    }

    if (RESERVED_SLUGS.has(slug)) {
        return `"${slug}" is reserved and cannot be used.`;
    }

    return null;
}

/**
 * Explain why a game name is unusable, or return null when it is fine.
 */
export function validateGameName(name: string): string | null {
    const trimmed = name.trim();

    if (trimmed.length < GAME_NAME_MIN_LENGTH) {
        return `The name must be at least ${GAME_NAME_MIN_LENGTH} characters long.`;
    }

    if (trimmed.length > GAME_NAME_MAX_LENGTH) {
        return `The name may not be longer than ${GAME_NAME_MAX_LENGTH} characters.`;
    }

    return null;
}
