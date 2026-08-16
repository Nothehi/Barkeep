import { router } from '@inertiajs/react';
import sessions from '@/routes/playtests/sessions';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Begin a session.
 *
 * Nothing is sent: the start time comes from the server's clock, because it is
 * the anchor the duration, the timeline and the elapsed counter all hang off.
 */
export function startSession(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    options: MutationOptions = {},
): void {
    router.post(
        sessions.start.url({ workspace, game, playtest, session }),
        {},
        toVisitOptions(options),
    );
}
