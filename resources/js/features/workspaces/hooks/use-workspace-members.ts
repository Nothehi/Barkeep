import { usePage } from '@inertiajs/react';
import type { WorkspaceMember, WorkspaceRole } from '../types/workspace';

export type UseWorkspaceMembersResult = {
    members: WorkspaceMember[];
    owner: WorkspaceMember | null;
    admins: WorkspaceMember[];

    /** The signed in account's own membership, if it is in the list. */
    self: WorkspaceMember | null;

    /** Members other than the signed in account, in server order. */
    others: WorkspaceMember[];
};

/**
 * The members of the workspace the current screen is about.
 *
 * Reads the page's `members` prop rather than fetching, so the list is always
 * the one the server just authorized and sent. Ordering is the server's:
 * owner, then administrators, then members by join date.
 */
export function useWorkspaceMembers(
    currentUserId?: string,
): UseWorkspaceMembersResult {
    const page = usePage<{ members?: { data: WorkspaceMember[] } }>();
    const members = page.props.members?.data ?? [];

    const withRole = (role: WorkspaceRole) =>
        members.filter((member) => member.role === role);

    return {
        members,
        owner: withRole('owner')[0] ?? null,
        admins: withRole('admin'),
        self: members.find((member) => member.user_id === currentUserId) ?? null,
        others: members.filter((member) => member.user_id !== currentUserId),
    };
}
