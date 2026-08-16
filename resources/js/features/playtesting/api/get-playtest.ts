import playtestsApi from '@/routes/api/workspaces/games/playtests';
import type { Playtest } from '../types/playtest';
import { request, unwrap } from './client';

/**
 * Fetch one playtest in full, with its permissions and available moves.
 */
export async function getPlaytest(
    workspace: string,
    game: string,
    playtest: string,
    signal?: AbortSignal,
): Promise<Playtest> {
    return unwrap(
        await request<{ data: Playtest }>({
            method: 'get',
            url: playtestsApi.show.url({ workspace, game, playtest }),
            signal,
        }),
    );
}
