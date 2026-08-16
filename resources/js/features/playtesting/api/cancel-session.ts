import { router } from '@inertiajs/react';
import sessions from '@/routes/playtests/sessions';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Call a session off, before it started or part way through.
 *
 * Whatever was recorded before the cancellation stays — the reason a session
 * was abandoned is usually among its observations.
 */
export function cancelSession(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    options: MutationOptions = {},
): void {
    router.post(
        sessions.cancel.url({ workspace, game, playtest, session }),
        {},
        toVisitOptions(options),
    );
}
