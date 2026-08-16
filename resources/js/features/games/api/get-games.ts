import gamesApi from '@/routes/api/workspaces/games';
import type { GameFilters, GameSummary } from '../types/game';
import { request, unwrap } from './client';

/**
 * Fetch the games in a workspace.
 *
 * Scoped to the workspace server side, so this can never return a game from
 * somewhere else — the workspace is part of the address, not a filter the
 * server applies afterwards.
 */
export async function getGames(
    workspace: string,
    filters: Partial<GameFilters> = {},
    signal?: AbortSignal,
): Promise<GameSummary[]> {
    const query: Record<string, string> = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.status) {
        query.status = filters.status;
    }

    if (filters.design_phase) {
        query.design_phase = filters.design_phase;
    }

    return unwrap(
        await request<{ data: GameSummary[] }>({
            method: 'get',
            url: gamesApi.index.url(workspace, { query }),
            signal,
        }),
    );
}
