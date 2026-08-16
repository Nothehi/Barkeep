import { router } from '@inertiajs/react';
import sessions from '@/routes/playtests/sessions';
import type { CreateSessionInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Schedule another sitting and go to it.
 *
 * `preserveScroll` is off by default here because this one navigates: the
 * reason somebody creates a session is almost always that they are about to
 * run it, so the server redirects to the session screen.
 */
export function createSession(
    workspace: string,
    game: string,
    playtest: string,
    input: CreateSessionInput,
    options: MutationOptions = {},
): void {
    router.post(
        sessions.store.url({ workspace, game, playtest }),
        { ...input },
        toVisitOptions({ preserveScroll: false, ...options }),
    );
}
