import { useCallback, useState } from 'react';
import { removeMember } from '../api';
import { useWorkspacePermissions } from '../hooks/use-workspace-permissions';
import type { Workspace, WorkspaceMember } from '../types/workspace';
import ChangeMemberRoleDialog from './change-member-role-dialog';
import WorkspaceMemberRow from './workspace-member-row';

type WorkspaceMemberListProps = {
    workspace: Workspace;
    members: WorkspaceMember[];
    currentUserId?: string;
};

/**
 * The workspace's people, with the actions the caller is allowed to take.
 */
export default function WorkspaceMemberList({
    workspace,
    members,
    currentUserId,
}: WorkspaceMemberListProps) {
    const permissions = useWorkspacePermissions(workspace);
    const [memberBeingChanged, setMemberBeingChanged] =
        useState<WorkspaceMember | null>(null);

    const handleRemove = useCallback(
        (member: WorkspaceMember) => {
            const name = member.user?.name ?? 'this member';

            if (
                !window.confirm(
                    `Remove ${name} from ${workspace.name}? They will lose access immediately.`,
                )
            ) {
                return;
            }

            removeMember(workspace.slug, member.id);
        },
        [workspace.slug, workspace.name],
    );

    if (members.length === 0) {
        return (
            <p className="rounded-lg border border-dashed px-4 py-8 text-center text-sm text-muted-foreground">
                This workspace has no members yet.
            </p>
        );
    }

    return (
        <>
            <ul className="divide-y rounded-lg border">
                {members.map((member) => (
                    <WorkspaceMemberRow
                        key={member.id}
                        member={member}
                        permissions={permissions}
                        isSelf={member.user_id === currentUserId}
                        onChangeRole={setMemberBeingChanged}
                        onRemove={handleRemove}
                    />
                ))}
            </ul>

            <ChangeMemberRoleDialog
                workspace={workspace}
                member={memberBeingChanged}
                onClose={() => setMemberBeingChanged(null)}
            />
        </>
    );
}
