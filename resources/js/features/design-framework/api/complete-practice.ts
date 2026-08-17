import { router } from '@inertiajs/react';
import practices from '@/routes/games/framework/practices';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Tick — or untick — one of the methodology's activities.
 *
 * One call for both directions, because a checkbox has two of them. Unticking
 * is `completed: false` on the same route rather than a DELETE: what is being
 * withdrawn is this studio's own completion, and expressing that as a delete
 * on a practice would read as removing the methodology's content.
 */
export function completePractice(
    workspace: string,
    game: string,
    practice: string,
    completed = true,
    notes: string | null = null,
    options: MutationOptions = {},
): void {
    router.post(
        practices.complete.url({ workspace, game, practice }),
        { completed, notes },
        toVisitOptions(options),
    );
}
