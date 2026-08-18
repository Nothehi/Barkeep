import { Mail, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { revokeInvitation } from '../api';
import type { Workspace, WorkspaceInvitation } from '../types/workspace';
import { WORKSPACE_ROLE_LABEL } from '../types/workspace';

type WorkspaceInvitationListProps = {
    workspace: Workspace;
    invitations: WorkspaceInvitation[];
};

/**
 * The invitations a workspace is still waiting on.
 *
 * Only ones that can still be redeemed appear — the server leaves out
 * anything expired, accepted or revoked, so nothing here is offering to
 * revoke something already dead.
 */
export default function WorkspaceInvitationList({
    workspace,
    invitations,
}: WorkspaceInvitationListProps) {
    const { t } = useTranslation();
    const { formatDate } = useFormatters();

    if (invitations.length === 0) {
        return (
            <p className="rounded-lg border border-dashed px-4 py-8 text-center text-sm text-muted-foreground">
                {t('No invitations are waiting to be accepted.')}
            </p>
        );
    }

    return (
        <ul className="divide-y rounded-lg border">
            {invitations.map((invitation) => (
                <li
                    key={invitation.id}
                    className="flex items-center gap-3 px-4 py-3"
                >
                    <Mail className="size-5 shrink-0 text-muted-foreground" />

                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium" dir="auto">
                            {invitation.email}
                        </p>
                        <p className="truncate text-sm text-muted-foreground">
                            {t('Expires :date', {
                                date: formatDate(invitation.expires_at),
                            })}
                        </p>
                    </div>

                    <Badge variant="secondary">
                        {t(WORKSPACE_ROLE_LABEL[invitation.role])}
                    </Badge>

                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={t('Revoke the invitation for :email', {
                            email: invitation.email,
                        })}
                        onClick={() =>
                            revokeInvitation(workspace.slug, invitation.id)
                        }
                    >
                        <X className="size-4" />
                    </Button>
                </li>
            ))}
        </ul>
    );
}
