import { router } from '@inertiajs/react';
import workspaces from '@/routes/workspaces';
import type { CreateWorkspaceInput } from '../schemas/workspace';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Open a new workspace and follow the server into it.
 *
 * A visit rather than a JSON call: the server answers with a redirect to the
 * new workspace, which is exactly the behaviour wanted — creating a workspace
 * should land you inside it.
 */
export function createWorkspace(
    input: CreateWorkspaceInput,
    options: MutationOptions = {},
): void {
    router.post(workspaces.store.url(), { ...input }, toVisitOptions(options));
}
