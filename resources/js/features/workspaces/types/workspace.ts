/**
 * The workspace shapes the server sends.
 *
 * Mirrors the resources under
 * `Modules\Workspace\Presentation\Http\Resources`. Those are the
 * authoritative shape — when one changes, change it here too.
 */

import type { User } from '@/features/auth';

export type WorkspaceStatus = 'active' | 'archived' | 'suspended';

export type WorkspaceRole = 'owner' | 'admin' | 'member';

export type InvitationStatus = 'pending' | 'accepted' | 'revoked' | 'expired';

/**
 * What the signed in account may do in a workspace.
 *
 * Computed from the policy server side, so this is the same answer the
 * request would get. It decides what the interface offers, never what the
 * server allows — every one of these is checked again on the request that
 * performs the action.
 */
export type WorkspacePermissions = {
    canView: boolean;
    canUpdate: boolean;
    canManageMembers: boolean;
    canInviteMembers: boolean;
    canRemoveMembers: boolean;
    canChangeRoles: boolean;
    canTransferOwnership: boolean;
    canArchive: boolean;
    canLeave: boolean;
};

export type Workspace = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    status: WorkspaceStatus;
    owner_id: string;
    members_count?: number;
    archived_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    permissions: WorkspacePermissions;
};

export type WorkspaceMember = {
    id: string;
    workspace_id: string;
    user_id: string;
    role: WorkspaceRole;
    joined_at: string;
    user?: User;
};

export type WorkspaceInvitation = {
    id: string;
    workspace_id: string;
    email: string;
    role: WorkspaceRole;
    status: InvitationStatus;
    expires_at: string;
    accepted_at: string | null;
    revoked_at: string | null;
    created_at: string | null;
    invited_by?: User;
};

/**
 * The trimmed invitation shown to whoever holds the link.
 *
 * Deliberately smaller than {@link WorkspaceInvitation}: the recipient is not
 * a member yet, so they are told which workspace and as what, and nothing
 * else about it.
 */
export type PublicWorkspaceInvitation = {
    email: string;
    role: WorkspaceRole;
    status: InvitationStatus;
    expires_at: string;
    workspace: {
        name: string | null;
        slug: string | null;
    };
};

/**
 * The workspace navigation data shared with every Inertia page.
 *
 * `available` is scoped to membership server side, so the switcher cannot
 * offer somewhere the account does not belong. `current` is read from the URL
 * the server actually resolved, not from client state.
 */
export type WorkspaceNavigation = {
    available: Workspace[];
    current: string | null;
};

/**
 * The roles an administrator can hand out.
 *
 * Ownership is absent on purpose — it moves through an explicit transfer, and
 * the server rejects it anywhere a role is submitted.
 */
export type AssignableWorkspaceRole = Extract<
    WorkspaceRole,
    'admin' | 'member'
>;
