import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { updateMember } from '../api';
import type {
    AssignableWorkspaceRole,
    Workspace,
    WorkspaceMember,
} from '../types/workspace';

type ChangeMemberRoleDialogProps = {
    workspace: Workspace;
    member: WorkspaceMember | null;
    onClose: () => void;
};

/**
 * Promotes or demotes a member.
 *
 * Only administrator and member are offered. Ownership is not a role you can
 * pick here — it moves through the transfer dialog, and the server rejects it
 * on this route whatever is sent.
 */
export default function ChangeMemberRoleDialog({
    workspace,
    member,
    onClose,
}: ChangeMemberRoleDialogProps) {
    /**
     * The role the dialog is showing.
     *
     * Held as a choice made *for a particular member* rather than as a plain
     * value, so opening the dialog on somebody else falls back to their
     * current role without an effect having to reset it.
     */
    const [choice, setChoice] = useState<{
        memberId: string;
        role: AssignableWorkspaceRole;
    } | null>(null);

    const [processing, setProcessing] = useState(false);

    const currentRole: AssignableWorkspaceRole =
        member === null || member.role === 'owner' ? 'member' : member.role;

    const role =
        choice !== null && choice.memberId === member?.id
            ? choice.role
            : currentRole;

    const submit = () => {
        if (!member) {
            return;
        }

        setProcessing(true);

        updateMember(workspace.slug, member.id, role, {
            onSuccess: onClose,
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <Dialog
            open={member !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Change role</DialogTitle>
                    <DialogDescription>
                        Choose what {member?.user?.name ?? 'this member'} can do
                        in {workspace.name}.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-2">
                    <Label htmlFor="member-role">Role</Label>

                    <Select
                        value={role}
                        onValueChange={(value) =>
                            member &&
                            setChoice({
                                memberId: member.id,
                                role: value as AssignableWorkspaceRole,
                            })
                        }
                    >
                        <SelectTrigger id="member-role" className="w-full">
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value="member">
                                Member — can take part in the workspace
                            </SelectItem>
                            <SelectItem value="admin">
                                Admin — can also manage members and settings
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Cancel
                    </Button>

                    <Button
                        onClick={submit}
                        disabled={processing || role === member?.role}
                        data-test="save-member-role-button"
                    >
                        {processing && <Spinner />}
                        Save role
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
