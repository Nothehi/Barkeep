import { router } from '@inertiajs/react';
import games from '@/routes/games';
import type { GameStatus } from '../types/game';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Move a game to another point in its lifecycle.
 *
 * Only the moves the server offered on the game itself will be accepted —
 * the transition matrix lives in the domain, and this posts an intent to it
 * rather than setting a field.
 */
export function changeGameStatus(
    workspace: string,
    game: string,
    status: GameStatus,
    options: MutationOptions = {},
): void {
    router.post(
        games.status.url({ workspace, game }),
        { status },
        toVisitOptions(options),
    );
}
