import { router } from '@inertiajs/react';
import versions from '@/routes/games/versions';
import type { CreateGameVersionInput } from '../schemas/game';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Record a new iteration of a game.
 *
 * No version number is sent, and none would be honoured: the server allocates
 * the next one in sequence and redirects to it.
 */
export function createGameVersion(
    workspace: string,
    game: string,
    input: CreateGameVersionInput,
    options: MutationOptions = {},
): void {
    router.post(
        versions.store.url({ workspace, game }),
        { ...input },
        toVisitOptions(options),
    );
}
