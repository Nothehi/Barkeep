import invitations from '@/routes/api/workspaces/members/invitations';
import type { WorkspaceInvitation } from '../types/workspace';
import { request, unwrap } from './client';

/**
 * Fetch a workspace's outstanding invitations.
 *
 * Administrators only — a plain member gets a 403.
 */
export async function getInvitations(
    slug: string,
    signal?: AbortSignal,
): Promise<WorkspaceInvitation[]> {
    return unwrap(
        await request<{ data: WorkspaceInvitation[] }>({
            method: 'get',
            url: invitations.index.url(slug),
            signal,
        }),
    );
}
