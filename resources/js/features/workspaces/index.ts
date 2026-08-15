/**
 * The Workspace module's client surface.
 *
 * Pages under `resources/js/pages/workspaces` are thin wrappers over the page
 * components here; Inertia requires page components to live under `pages/`,
 * so the reusable parts live in the feature instead.
 */

export {
    archiveWorkspace,
    createWorkspace,
    getInvitations,
    getMembers,
    getWorkspace,
    getWorkspaces,
    inviteMember,
    leaveWorkspace,
    removeMember,
    revokeInvitation,
    transferOwnership,
    updateMember,
    updateWorkspace,
    WorkspaceApiError,
} from './api';
export type { MutationOptions } from './api';
export { default as ChangeMemberRoleDialog } from './components/change-member-role-dialog';
export { default as InviteMemberDialog } from './components/invite-member-dialog';
export { default as TransferOwnershipDialog } from './components/transfer-ownership-dialog';
export { default as WorkspaceCard } from './components/workspace-card';
export { default as WorkspaceHeader } from './components/workspace-header';
export { default as WorkspaceInvitationList } from './components/workspace-invitation-list';
export { default as WorkspaceMemberList } from './components/workspace-member-list';
export { default as WorkspaceMemberRow } from './components/workspace-member-row';
export { default as WorkspaceSwitcher } from './components/workspace-switcher';
export { useCreateWorkspace } from './hooks/use-create-workspace';
export type { UseCreateWorkspaceResult } from './hooks/use-create-workspace';
export { useWorkspace } from './hooks/use-workspace';
export { useWorkspaceMembers } from './hooks/use-workspace-members';
export type { UseWorkspaceMembersResult } from './hooks/use-workspace-members';
export { useWorkspacePermissions } from './hooks/use-workspace-permissions';
export { useWorkspaces } from './hooks/use-workspaces';
export type { UseWorkspacesResult } from './hooks/use-workspaces';
export { default as CreateWorkspacePage } from './pages/create-workspace-page';
export { default as WorkspaceInvitationPage } from './pages/workspace-invitation-page';
export { default as WorkspaceListPage } from './pages/workspace-list-page';
export { default as WorkspaceMembersPage } from './pages/workspace-members-page';
export { default as WorkspacePage } from './pages/workspace-page';
export { default as WorkspaceSettingsPage } from './pages/workspace-settings-page';
export * from './schemas/workspace';
export type {
    AssignableWorkspaceRole,
    InvitationStatus,
    PublicWorkspaceInvitation,
    Workspace,
    WorkspaceInvitation,
    WorkspaceMember,
    WorkspaceNavigation,
    WorkspacePermissions,
    WorkspaceRole,
    WorkspaceStatus,
} from './types/workspace';
