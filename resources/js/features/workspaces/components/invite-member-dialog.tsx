import { UserPlus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { inviteMember } from '../api';
import type { AssignableWorkspaceRole, Workspace } from '../types/workspace';

type InviteMemberDialogProps = {
    workspace: Workspace;
};

/**
 * Sends a workspace invitation to an email address.
 *
 * Addressed to an address rather than to an account on purpose: the person
 * may not have registered yet, and the link walks them through Identity's
 * registration before they can accept.
 */
export default function InviteMemberDialog({
    workspace,
}: InviteMemberDialogProps) {
    const [open, setOpen] = useState(false);
    const [email, setEmail] = useState('');
    const [role, setRole] = useState<AssignableWorkspaceRole>('member');
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        setProcessing(true);
        setErrors({});

        inviteMember(
            workspace.slug,
            { email, role },
            {
                onSuccess: () => {
                    setOpen(false);
                    setEmail('');
                    setRole('member');
                },
                onError: setErrors,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button data-test="invite-member-button">
                    <UserPlus className="size-4" />
                    Invite member
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Invite to {workspace.name}</DialogTitle>
                    <DialogDescription>
                        We will email an invitation link. It expires in two
                        weeks and can only be used by the address you enter.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="invite-email">Email address</Label>

                        <Input
                            id="invite-email"
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder="designer@example.com"
                            autoComplete="off"
                            required
                        />

                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="invite-role">Role</Label>

                        <Select
                            value={role}
                            onValueChange={(value) =>
                                setRole(value as AssignableWorkspaceRole)
                            }
                        >
                            <SelectTrigger id="invite-role" className="w-full">
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value="member">Member</SelectItem>
                                <SelectItem value="admin">Admin</SelectItem>
                            </SelectContent>
                        </Select>

                        <InputError message={errors.role} />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Cancel
                    </Button>

                    <Button
                        onClick={submit}
                        disabled={processing || email.trim() === ''}
                        data-test="send-invitation-button"
                    >
                        {processing && <Spinner />}
                        Send invitation
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
