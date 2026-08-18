import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAuth } from '@/features/auth';
import { useTranslation } from '@/lib/i18n';
import { login, register } from '@/routes';
import workspaceInvitations from '@/routes/workspace-invitations';
import type { PublicWorkspaceInvitation } from '../types/workspace';
import { WORKSPACE_ROLE_LABEL } from '../types/workspace';

type WorkspaceInvitationPageProps = {
    invitation: { data: PublicWorkspaceInvitation };
    token: string;
    matchesAccount: boolean;
};

/**
 * Where an invitation link lands.
 *
 * Shown to people who may have no account yet, so it says only which
 * workspace the link is for and as what. Somebody who has not registered is
 * sent through Identity's registration and comes back to this same link —
 * Workspace never creates an account of its own.
 *
 * Everything this page decides is cosmetic. Accepting is authorized on the
 * server against the invitation and the session, whatever the page showed.
 */
export default function WorkspaceInvitationPage({
    invitation: { data: invitation },
    token,
    matchesAccount,
}: WorkspaceInvitationPageProps) {
    const { isAuthenticated } = useAuth();
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);

    const workspaceName = invitation.workspace.name ?? t('a workspace');
    const isPending = invitation.status === 'pending';

    /**
     * The status is only ever read inside the "no longer valid" sentence, so
     * it is worded as a past participle that fits it rather than as a badge.
     */
    const statusWording: Record<string, string> = {
        accepted: t('accepted'),
        revoked: t('revoked'),
        expired: t('expired'),
        pending: t('pending'),
    };

    const accept = () => {
        setProcessing(true);

        router.post(
            workspaceInvitations.accept.url(token),
            {},
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <>
            <Head title={t('Join :workspace', { workspace: workspaceName })} />

            <div className="mx-auto flex min-h-svh max-w-md flex-col justify-center gap-6 px-4 py-12">
                <div className="space-y-2 text-center">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {t('Join :workspace', { workspace: workspaceName })}
                    </h1>

                    <p className="text-sm text-muted-foreground">
                        {t('You have been invited to :workspace as :role.', {
                            workspace: workspaceName,
                            role: t(WORKSPACE_ROLE_LABEL[invitation.role]),
                        })}
                    </p>
                </div>

                {!isPending && (
                    <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                        {t(
                            'This invitation is no longer valid — it has been :status. Ask an administrator of :workspace to send a new one.',
                            {
                                status:
                                    statusWording[invitation.status] ??
                                    invitation.status,
                                workspace: workspaceName,
                            },
                        )}
                    </p>
                )}

                {isPending && !isAuthenticated && (
                    <div className="space-y-4 text-center">
                        <p className="text-sm text-muted-foreground">
                            {t(
                                'Sign in as :email to accept, or create an account with that address.',
                                { email: invitation.email },
                            )}
                        </p>

                        <div className="flex flex-col gap-2">
                            <Button asChild>
                                <a href={register.url()}>
                                    {t('Create an account')}
                                </a>
                            </Button>

                            <p className="text-sm text-muted-foreground">
                                {t('Already have one?')}{' '}
                                <TextLink href={login()}>
                                    {t('Sign in')}
                                </TextLink>
                            </p>
                        </div>
                    </div>
                )}

                {isPending && isAuthenticated && !matchesAccount && (
                    <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                        {t(
                            'This invitation was sent to :email. Sign in with that address to accept it.',
                            { email: invitation.email },
                        )}
                    </p>
                )}

                {isPending && isAuthenticated && matchesAccount && (
                    <Button
                        onClick={accept}
                        disabled={processing}
                        data-test="accept-invitation-button"
                    >
                        {processing && <Spinner />}
                        {t('Accept invitation')}
                    </Button>
                )}
            </div>
        </>
    );
}
