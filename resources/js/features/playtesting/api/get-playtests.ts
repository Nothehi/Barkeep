import playtestsApi from '@/routes/api/workspaces/games/playtests';
import type { PlaytestStatusFilter } from '../schemas/playtest';
import type { PlaytestSummary } from '../types/playtest';
import { request, unwrap } from './client';

/**
 * Fetch a game's playtests, most recently planned first.
 *
 * The filters can only narrow. The game is part of the address rather than a
 * parameter, so there is no call here that reaches another project's
 * playtests.
 */
export async function getPlaytests(
    workspace: string,
    game: string,
    filters: { search?: string; status?: PlaytestStatusFilter } = {},
    signal?: AbortSignal,
): Promise<PlaytestSummary[]> {
    const query: Record<string, string> = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.status) {
        query.status = filters.status;
    }

    return unwrap(
        await request<{ data: PlaytestSummary[] }>({
            method: 'get',
            url: playtestsApi.index.url({ workspace, game }, { query }),
            signal,
        }),
    );
}
