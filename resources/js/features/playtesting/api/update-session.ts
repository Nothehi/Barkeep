import { router } from '@inertiajs/react';
import sessions from '@/routes/playtests/sessions';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Change a session that has not ended.
 *
 * Notes are what this is for: they accumulate as the session runs, so they are
 * saved repeatedly rather than once at the end.
 */
export function updateSession(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    input: { planned_at?: string; location?: string; notes?: string },
    options: MutationOptions = {},
): void {
    router.patch(
        sessions.update.url({ workspace, game, playtest, session }),
        { ...input },
        toVisitOptions(options),
    );
}
