/**
 * Client-side shapes and checks for workspace forms.
 *
 * These mirror `Modules\Workspace\Domain\ValueObjects\WorkspaceSlug` and the
 * form requests beside it. They exist to give immediate feedback while
 * somebody types; the server validates every field again and its answer wins.
 */

import type { AssignableWorkspaceRole } from '../types/workspace';

export const WORKSPACE_NAME_MIN_LENGTH = 2;
export const WORKSPACE_NAME_MAX_LENGTH = 120;
export const WORKSPACE_SLUG_MIN_LENGTH = 3;
export const WORKSPACE_SLUG_MAX_LENGTH = 48;
export const WORKSPACE_DESCRIPTION_MAX_LENGTH = 2000;

/**
 * Addresses the platform keeps for itself.
 *
 * Kept in step with the `RESERVED` list on the value object.
 */
const RESERVED_SLUGS = new Set([
    'admin',
    'api',
    'app',
    'assets',
    'auth',
    'billing',
    'build',
    'dashboard',
    'help',
    'home',
    'login',
    'logout',
    'new',
    'register',
    'settings',
    'signup',
    'storage',
    'support',
    'system',
    'up',
    'user',
    'users',
    'workspace',
    'workspaces',
]);

const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export type CreateWorkspaceInput = {
    name: string;
    slug: string;
    description: string;
};

export type UpdateWorkspaceInput = CreateWorkspaceInput;

export type InviteMemberInput = {
    email: string;
    role: AssignableWorkspaceRole;
};

export type ChangeMemberRoleInput = {
    role: AssignableWorkspaceRole;
};

export type TransferOwnershipInput = {
    member_id: string;
    role: AssignableWorkspaceRole;
};

export const emptyCreateWorkspaceInput: CreateWorkspaceInput = {
    name: '',
    slug: '',
    description: '',
};

/**
 * Suggest an address for a workspace name.
 *
 * Used to fill the slug field as somebody types the name, so they can see and
 * override what their URL will be. The server derives its own when the field
 * is left empty, and resolves collisions there.
 */
export function suggestWorkspaceSlug(name: string): string {
    return name
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, WORKSPACE_SLUG_MAX_LENGTH)
        .replace(/-+$/, '');
}

/**
 * Explain why an address is unusable, or return null when it is fine.
 *
 * An empty value is treated as valid: leaving the field blank asks the server
 * to pick an address, which is a legitimate choice rather than an error.
 */
export function validateWorkspaceSlug(slug: string): string | null {
    if (slug === '') {
        return null;
    }

    if (slug.length < WORKSPACE_SLUG_MIN_LENGTH) {
        return `The address must be at least ${WORKSPACE_SLUG_MIN_LENGTH} characters long.`;
    }

    if (slug.length > WORKSPACE_SLUG_MAX_LENGTH) {
        return `The address may not be longer than ${WORKSPACE_SLUG_MAX_LENGTH} characters.`;
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
 * Explain why a workspace name is unusable, or return null when it is fine.
 */
export function validateWorkspaceName(name: string): string | null {
    const trimmed = name.trim();

    if (trimmed.length < WORKSPACE_NAME_MIN_LENGTH) {
        return `The name must be at least ${WORKSPACE_NAME_MIN_LENGTH} characters long.`;
    }

    if (trimmed.length > WORKSPACE_NAME_MAX_LENGTH) {
        return `The name may not be longer than ${WORKSPACE_NAME_MAX_LENGTH} characters.`;
    }

    return null;
}
