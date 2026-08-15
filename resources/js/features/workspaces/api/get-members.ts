import members from '@/routes/api/workspaces/members';
import type { WorkspaceMember } from '../types/workspace';
import { request, unwrap } from './client';

/**
 * Fetch a workspace's members.
 */
export async function getMembers(
    slug: string,
    signal?: AbortSignal,
): Promise<WorkspaceMember[]> {
    return unwrap(
        await request<{ data: WorkspaceMember[] }>({
            method: 'get',
            url: members.index.url(slug),
            signal,
        }),
    );
}
