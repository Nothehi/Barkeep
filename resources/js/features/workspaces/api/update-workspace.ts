import { router } from '@inertiajs/react';
import workspaces from '@/routes/workspaces';
import type { UpdateWorkspaceInput } from '../schemas/workspace';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Save a workspace's general settings.
 *
 * The address may change here, so the server redirects to wherever the
 * workspace now lives rather than back to the URL that was submitted.
 */
export function updateWorkspace(
    slug: string,
    input: UpdateWorkspaceInput,
    options: MutationOptions = {},
): void {
    router.patch(
        workspaces.update.url(slug),
        { ...input },
        toVisitOptions(options),
    );
}
