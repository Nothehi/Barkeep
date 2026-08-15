import { router } from '@inertiajs/react';
import workspaces from '@/routes/workspaces';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Retire a workspace.
 *
 * Nothing is deleted — the workspace becomes read-only and drops out of the
 * switcher's active list.
 */
export function archiveWorkspace(
    slug: string,
    options: MutationOptions = {},
): void {
    router.post(workspaces.archive.url(slug), {}, toVisitOptions(options));
}
