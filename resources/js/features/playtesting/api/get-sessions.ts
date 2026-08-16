import sessionsApi from '@/routes/api/workspaces/games/playtests/sessions';
import type { PlaytestSession } from '../types/playtest';
import { request, unwrap } from './client';

/**
 * Fetch a playtest's sittings, earliest first.
 *
 * Forwards rather than backwards, because sessions are read as a sequence:
 * "by the third group they had stopped asking about scoring" only makes sense
 * in order.
 */
export async function getSessions(
    workspace: string,
    game: string,
    playtest: string,
    signal?: AbortSignal,
): Promise<PlaytestSession[]> {
    return unwrap(
        await request<{ data: PlaytestSession[] }>({
            method: 'get',
            url: sessionsApi.index.url({ workspace, game, playtest }),
            signal,
        }),
    );
}
