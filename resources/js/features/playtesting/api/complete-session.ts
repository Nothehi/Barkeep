import { router } from '@inertiajs/react';
import sessions from '@/routes/playtests/sessions';
import type { CompleteSessionInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * End a session.
 *
 * The last thing done to it: afterwards it accepts no more participants,
 * observations or feedback, which is what makes everything in it datable.
 */
export function completeSession(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    input: CompleteSessionInput,
    options: MutationOptions = {},
): void {
    router.post(
        sessions.complete.url({ workspace, game, playtest, session }),
        { ...input },
        toVisitOptions(options),
    );
}
