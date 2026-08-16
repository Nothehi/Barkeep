import { router } from '@inertiajs/react';
import feedback from '@/routes/playtests/sessions/feedback';
import type { CreateFeedbackInput } from '../schemas/playtest';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Correct a piece of feedback while the session is still open.
 *
 * Clearing the rating is a real edit rather than an omission: a null means the
 * participant did not put a number on it, which has to stay different from a
 * low score.
 */
export function updateFeedback(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    id: string,
    input: CreateFeedbackInput,
    options: MutationOptions = {},
): void {
    router.patch(
        feedback.update.url({
            workspace,
            game,
            playtest,
            session,
            feedback: id,
        }),
        {
            content: input.content,
            participant_id: input.participant_id,
            rating: input.rating === '' ? null : Number(input.rating),
        },
        toVisitOptions(options),
    );
}
