import { router } from '@inertiajs/react';
import games from '@/routes/games';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Put a game away.
 *
 * Nothing is deleted — the game and every version of it stay readable, and
 * nothing about them can change again.
 */
export function archiveGame(
    workspace: string,
    game: string,
    options: MutationOptions = {},
): void {
    router.post(
        games.archive.url({ workspace, game }),
        {},
        toVisitOptions(options),
    );
}
