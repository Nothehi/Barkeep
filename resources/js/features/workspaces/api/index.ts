/**
 * The workspace feature's server calls.
 *
 * Split by direction rather than by resource:
 *
 * - reads go over the JSON API and return data (`get*`);
 * - writes are Inertia visits and return nothing, because the server answers
 *   them with a redirect and a flash message.
 */

export { archiveWorkspace } from './archive-workspace';
export { WorkspaceApiError } from './client';
export { createWorkspace } from './create-workspace';
export { getInvitations } from './get-invitations';
export { getMembers } from './get-members';
export { getWorkspace } from './get-workspace';
export { getWorkspaces } from './get-workspaces';
export { inviteMember } from './invite-member';
export { leaveWorkspace } from './leave-workspace';
export type { MutationOptions } from './mutation';
export { removeMember } from './remove-member';
export { revokeInvitation } from './revoke-invitation';
export { transferOwnership } from './transfer-ownership';
export { updateMember } from './update-member';
export { updateWorkspace } from './update-workspace';
