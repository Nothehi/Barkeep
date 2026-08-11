import workspacesApi from '@/routes/api/workspaces';
import type { Workspace } from '../types/workspace';
import { request, unwrap } from './client';

/**
 * Fetch a single workspace by its address.
 *
 * Rejects with a 404 when the account is not a member, which is deliberate:
 * a non-member is not told that the workspace exists.
 */
export async function getWorkspace(
    slug: string,
    signal?: AbortSignal,
): Promise<Workspace> {
    return unwrap(
        await request<{ data: Workspace }>({
            method: 'get',
            url: workspacesApi.show.url(slug),
            signal,
        }),
    );
}
