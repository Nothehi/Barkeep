import { router } from '@inertiajs/react';
import feedbackRoutes from '@/routes/playtests/sessions/feedback';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Withdraw a piece of feedback while the session is still open.
 *
 * Once the session ends, what a participant said about a designer's game stops
 * being something the designer can remove.
 */
export function deleteFeedback(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    feedback: string,
    options: MutationOptions = {},
): void {
    router.delete(
        feedbackRoutes.destroy.url({
            workspace,
            game,
            playtest,
            session,
            feedback,
        }),
        toVisitOptions(options),
    );
}
