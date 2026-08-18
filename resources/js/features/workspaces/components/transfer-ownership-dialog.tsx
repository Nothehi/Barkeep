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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { transferOwnership } from '../api';
import type {
    AssignableWorkspaceRole,
    Workspace,
    WorkspaceMember,
} from '../types/workspace';

type TransferOwnershipDialogProps = {
    workspace: Workspace;
    members: WorkspaceMember[];
};

/**
 * Hands the workspace to another member.
 *
 * Only current members can receive it, and the outgoing owner picks what they
 * keep — administrator by default, since somebody who just handed over their
 * workspace usually still works in it.
 */
export default function TransferOwnershipDialog({
    workspace,
    members,
}: TransferOwnershipDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [memberId, setMemberId] = useState('');
    const [role, setRole] = useState<AssignableWorkspaceRole>('admin');
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const candidates = members.filter((member) => member.role !== 'owner');

    const submit = () => {
        setProcessing(true);
        setErrors({});

        transferOwnership(
            workspace.slug,
            { member_id: memberId, role },
            {
                onSuccess: () => {
                    setOpen(false);
                    setMemberId('');
                },
                onError: setErrors,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    disabled={candidates.length === 0}
                    data-test="transfer-ownership-button"
                >
                    {t('Transfer ownership')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Transfer ownership')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'The new owner takes full control of :workspace, including the ability to archive it. You cannot undo this yourself.',
                            { workspace: workspace.name },
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="new-owner">{t('New owner')}</Label>

                        <Select value={memberId} onValueChange={setMemberId}>
                            <SelectTrigger id="new-owner" className="w-full">
                                <SelectValue
                                    placeholder={t('Choose a member')}
                                />
                            </SelectTrigger>

                            <SelectContent>
                                {candidates.map((member) => (
                                    <SelectItem
                                        key={member.id}
                                        value={member.id}
                                        dir="auto"
                                    >
                                        {member.user?.name ?? member.user_id}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <InputError message={errors.member_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="outgoing-role">
                            {t('Your new role')}
                        </Label>

                        <Select
                            value={role}
                            onValueChange={(value) =>
                                setRole(value as AssignableWorkspaceRole)
                            }
                        >
                            <SelectTrigger
                                id="outgoing-role"
                                className="w-full"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value="admin">
                                    {t('Admin')}
                                </SelectItem>
                                <SelectItem value="member">
                                    {t('Member')}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <InputError message={errors.role} />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        {t('Cancel')}
                    </Button>

                    <Button
                        variant="destructive"
                        onClick={submit}
                        disabled={processing || memberId === ''}
                        data-test="confirm-transfer-ownership-button"
                    >
                        {processing && <Spinner />}
                        {t('Transfer ownership')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
