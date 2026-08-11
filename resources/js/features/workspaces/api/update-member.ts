import { router } from '@inertiajs/react';
import members from '@/routes/workspaces/members';
import type { AssignableWorkspaceRole } from '../types/workspace';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Promote or demote a member.
 *
 * Only the roles an administrator may hand out are accepted here, and the
 * server refuses ownership over this route regardless of what is sent.
 */
export function updateMember(
    slug: string,
    memberId: string,
    role: AssignableWorkspaceRole,
    options: MutationOptions = {},
): void {
    router.patch(
        members.update.url([slug, memberId]),
        { role },
        toVisitOptions(options),
    );
}
