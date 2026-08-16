import { router } from '@inertiajs/react';
import participants from '@/routes/playtests/sessions/participants';
import type { AddParticipantInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Seat somebody at a session.
 *
 * A name is enough. The account is optional and, when given, has to be
 * somebody who already shares the workspace — not a rule about who may play,
 * but about what linking an account discloses.
 */
export function addParticipant(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    input: AddParticipantInput,
    options: MutationOptions = {},
): void {
    router.post(
        participants.store.url({ workspace, game, playtest, session }),
        { ...input },
        toVisitOptions(options),
    );
}
