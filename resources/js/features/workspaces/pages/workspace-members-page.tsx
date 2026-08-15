import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { useAuth } from '@/features/auth';
import InviteMemberDialog from '../components/invite-member-dialog';
import WorkspaceHeader from '../components/workspace-header';
import WorkspaceInvitationList from '../components/workspace-invitation-list';
import WorkspaceMemberList from '../components/workspace-member-list';
import { useWorkspacePermissions } from '../hooks/use-workspace-permissions';
import type {
    Workspace,
    WorkspaceInvitation,
    WorkspaceMember,
} from '../types/workspace';

type WorkspaceMembersPageProps = {
    workspace: { data: Workspace };
    members: { data: WorkspaceMember[] };
    invitations: { data: WorkspaceInvitation[] };
};

/**
 * Who is in the workspace, and who has been asked to join.
 *
 * Any member may see the roster. Only administrators are sent the pending
 * invitations at all, so a plain member's page has nothing to hide.
 */
export default function WorkspaceMembersPage({
    workspace: { data: workspace },
    members,
    invitations,
}: WorkspaceMembersPageProps) {
    const { user } = useAuth();
    const permissions = useWorkspacePermissions(workspace);

    return (
        <>
            <Head title={`Members · ${workspace.name}`} />

            <div className="space-y-8 px-4 py-6">
                <WorkspaceHeader workspace={workspace} />

                <section className="space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <Heading
                            variant="small"
                            title="Members"
                            description="Everyone who can work in this workspace"
                        />

                        {permissions.canInviteMembers && (
                            <InviteMemberDialog workspace={workspace} />
                        )}
                    </div>

                    <WorkspaceMemberList
                        workspace={workspace}
                        members={members.data}
                        currentUserId={user?.id}
                    />
                </section>

                {permissions.canManageMembers && (
                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title="Pending invitations"
                            description="Invitations that have been sent but not accepted yet"
                        />

                        <WorkspaceInvitationList
                            workspace={workspace}
                            invitations={invitations.data}
                        />
                    </section>
                )}
            </div>
        </>
    );
}
