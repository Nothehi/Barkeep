import { router } from '@inertiajs/react';
import games from '@/routes/games';
import type { CreateGameInput } from '../schemas/game';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Start a new game and follow the server into it.
 *
 * A visit rather than a JSON call: the server answers with a redirect to the
 * new game, which is exactly the behaviour wanted — creating a game should
 * land you inside it.
 */
export function createGame(
    workspace: string,
    input: CreateGameInput,
    options: MutationOptions = {},
): void {
    router.post(
        games.store.url(workspace),
        { ...input },
        toVisitOptions(options),
    );
}
