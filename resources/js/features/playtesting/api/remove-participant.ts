import { router } from '@inertiajs/react';
import participants from '@/routes/playtests/sessions/participants';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Take somebody off a session.
 *
 * What they said, and what was noticed about them, survives with its
 * attribution dropped — a mistyped name must not be able to destroy evidence.
 */
export function removeParticipant(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    participant: string,
    options: MutationOptions = {},
): void {
    router.delete(
        participants.destroy.url({
            workspace,
            game,
            playtest,
            session,
            participant,
        }),
        toVisitOptions(options),
    );
}
