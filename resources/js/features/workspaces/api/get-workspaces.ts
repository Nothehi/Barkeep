import workspacesApi from '@/routes/api/workspaces';
import type { Workspace } from '../types/workspace';
import { request, unwrap } from './client';

/**
 * Fetch the workspaces the signed in account belongs to.
 *
 * Scoped to membership server side, so this can never return somewhere the
 * account does not belong.
 */
export async function getWorkspaces(
    signal?: AbortSignal,
): Promise<Workspace[]> {
    return unwrap(
        await request<{ data: Workspace[] }>({
            method: 'get',
            url: workspacesApi.index.url(),
            signal,
        }),
    );
}
