import { router } from '@inertiajs/react';
import observations from '@/routes/playtests/sessions/observations';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Withdraw an observation while the session is still open.
 */
export function deleteObservation(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    observation: string,
    options: MutationOptions = {},
): void {
    router.delete(
        observations.destroy.url({
            workspace,
            game,
            playtest,
            session,
            observation,
        }),
        toVisitOptions(options),
    );
}
