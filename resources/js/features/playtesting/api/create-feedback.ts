import { router } from '@inertiajs/react';
import feedback from '@/routes/playtests/sessions/feedback';
import type { CreateFeedbackInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Record what a participant said about a session.
 *
 * An empty rating is sent as null rather than as "", so "did not put a number
 * on it" reaches the server as an absence instead of as a value it has to
 * interpret. That distinction is what keeps a playtest's average rating
 * meaningful.
 */
export function createFeedback(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    input: CreateFeedbackInput,
    options: MutationOptions = {},
): void {
    router.post(
        feedback.store.url({ workspace, game, playtest, session }),
        {
            content: input.content,
            participant_id: input.participant_id,
            rating: input.rating === '' ? null : Number(input.rating),
        },
        toVisitOptions(options),
    );
}
