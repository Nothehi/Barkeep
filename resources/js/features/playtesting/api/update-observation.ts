import { router } from '@inertiajs/react';
import observations from '@/routes/playtests/sessions/observations';
import type { CreateObservationInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Correct an observation while the session is still open.
 *
 * The whole observation is replaced rather than patched: every field is on the
 * form, so a partial update would only add a way for the two to disagree.
 */
export function updateObservation(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    observation: string,
    input: CreateObservationInput,
    options: MutationOptions = {},
): void {
    router.patch(
        observations.update.url({
            workspace,
            game,
            playtest,
            session,
            observation,
        }),
        { ...input },
        toVisitOptions(options),
    );
}
