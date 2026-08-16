import playtestsApi from '@/routes/api/workspaces/games/playtests';
import type { PlaytestMetrics } from '../types/playtest';
import { request, unwrap } from './client';

/**
 * Fetch what a playtest has produced.
 *
 * Counted on read rather than stored, so this is always in step with the rows
 * it describes — there is no cached total that can drift.
 */
export async function getPlaytestSummary(
    workspace: string,
    game: string,
    playtest: string,
    signal?: AbortSignal,
): Promise<PlaytestMetrics> {
    return unwrap(
        await request<{ data: PlaytestMetrics }>({
            method: 'get',
            url: playtestsApi.summary.url({ workspace, game, playtest }),
            signal,
        }),
    );
}
