import { router } from '@inertiajs/react';
import invitations from '@/routes/workspaces/members/invitations';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Withdraw an invitation before it is redeemed.
 */
export function revokeInvitation(
    slug: string,
    invitationId: string,
    options: MutationOptions = {},
): void {
    router.delete(
        invitations.revoke.url([slug, invitationId]),
        toVisitOptions(options),
    );
}
