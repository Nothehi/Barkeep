import { router } from '@inertiajs/react';
import observations from '@/routes/playtests/sessions/observations';
import type { CreateObservationInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Record something noticed during a session.
 *
 * The most-used call in the module. It goes over an Inertia visit rather than
 * a JSON post so the observation comes back as part of the reloaded session —
 * an optimistic local insert would show a note in the timeline before anyone
 * knew whether the server had accepted it, and during a live session an
 * observation that quietly failed is one nobody ever gets back.
 */
export function createObservation(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    input: CreateObservationInput,
    options: MutationOptions = {},
): void {
    router.post(
        observations.store.url({ workspace, game, playtest, session }),
        { ...input },
        toVisitOptions(options),
    );
}
