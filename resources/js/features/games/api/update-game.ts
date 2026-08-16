import { router } from '@inertiajs/react';
import games from '@/routes/games';
import type { UpdateGameInput } from '../schemas/game';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Save a game's name, address and description.
 *
 * The address may change here, so the server redirects to wherever the game
 * now lives rather than back to the URL that was submitted.
 */
export function updateGame(
    workspace: string,
    game: string,
    input: UpdateGameInput,
    options: MutationOptions = {},
): void {
    router.patch(
        games.update.url({ workspace, game }),
        { ...input },
        toVisitOptions(options),
    );
}
