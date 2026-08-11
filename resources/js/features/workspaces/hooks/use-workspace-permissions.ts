import type { Workspace, WorkspacePermissions } from '../types/workspace';

/**
 * Everything denied — what an absent workspace is worth.
 */
const NOTHING: WorkspacePermissions = {
    canView: false,
    canUpdate: false,
    canManageMembers: false,
    canInviteMembers: false,
    canRemoveMembers: false,
    canChangeRoles: false,
    canTransferOwnership: false,
    canArchive: false,
    canLeave: false,
};

/**
 * What the signed in account may do in the given workspace.
 *
 * The answers come from the server's own policy, which is the only thing that
 * knows them; recomputing them here from a role would be a second, divergent
 * implementation of the rules.
 *
 * Use this to decide what the interface offers. It is not a security
 * boundary: hiding a button stops nobody from sending the request, and every
 * action is authorized again server side when they do.
 */
export function useWorkspacePermissions(
    workspace: Workspace | null | undefined,
): WorkspacePermissions {
    return workspace?.permissions ?? NOTHING;
}
