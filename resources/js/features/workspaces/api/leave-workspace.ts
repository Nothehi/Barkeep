import { router } from '@inertiajs/react';
import workspaces from '@/routes/workspaces';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Give up membership of a workspace.
 *
 * Refused for the owner: the server sends them to transfer ownership first.
 */
export function leaveWorkspace(
    slug: string,
    options: MutationOptions = {},
): void {
    router.post(workspaces.leave.url(slug), {}, toVisitOptions(options));
}
