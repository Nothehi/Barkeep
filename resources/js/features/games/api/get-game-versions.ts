import versionsApi from '@/routes/api/workspaces/games/versions';
import type { GameVersion } from '../types/game';
import { request, unwrap } from './client';

/**
 * Fetch a game's iterations, newest first.
 */
export async function getGameVersions(
    workspace: string,
    game: string,
    signal?: AbortSignal,
): Promise<GameVersion[]> {
    return unwrap(
        await request<{ data: GameVersion[] }>({
            method: 'get',
            url: versionsApi.index.url({ workspace, game }),
            signal,
        }),
    );
}
