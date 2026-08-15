import { router } from '@inertiajs/react';
import members from '@/routes/workspaces/members';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Remove somebody from a workspace.
 */
export function removeMember(
    slug: string,
    memberId: string,
    options: MutationOptions = {},
): void {
    router.delete(
        members.destroy.url([slug, memberId]),
        toVisitOptions(options),
    );
}
