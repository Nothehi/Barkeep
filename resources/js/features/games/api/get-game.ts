import gamesApi from '@/routes/api/workspaces/games';
import type { Game } from '../types/game';
import { request, unwrap } from './client';

/**
 * Fetch one game.
 *
 * The workspace is part of the address because a game address is only unique
 * inside one; asking for a game without saying whose is a question with no
 * answer.
 */
export async function getGame(
    workspace: string,
    game: string,
    signal?: AbortSignal,
): Promise<Game> {
    return unwrap(
        await request<{ data: Game }>({
            method: 'get',
            url: gamesApi.show.url({ workspace, game }),
            signal,
        }),
    );
}
