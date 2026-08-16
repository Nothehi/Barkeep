import { router } from '@inertiajs/react';
import playtests from '@/routes/playtests';
import type { CreatePlaytestInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Plan a playtest against a version of a game.
 *
 * The version is the only identifier sent, and the server proves it belongs to
 * the game in the address before writing anything — a playtest whose version
 * came from elsewhere would be a record of an evening nobody had.
 */
export function createPlaytest(
    workspace: string,
    game: string,
    input: CreatePlaytestInput,
    options: MutationOptions = {},
): void {
    router.post(
        playtests.store.url({ workspace, game }),
        { ...input },
        toVisitOptions(options),
    );
}
