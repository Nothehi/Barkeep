import { router } from '@inertiajs/react';
import members from '@/routes/workspaces/members';
import type { InviteMemberInput } from '../schemas/workspace';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Invite an email address to join a workspace.
 */
export function inviteMember(
    slug: string,
    input: InviteMemberInput,
    options: MutationOptions = {},
): void {
    router.post(
        members.invite.url(slug),
        { ...input },
        toVisitOptions(options),
    );
}
