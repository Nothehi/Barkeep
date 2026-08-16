import { router } from '@inertiajs/react';
import playtests from '@/routes/playtests';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Change a playtest's plan, its conclusion, or both.
 *
 * Only the fields actually sent are changed, and that matters here rather than
 * being a nicety: a completed playtest refuses every change to its plan and
 * still accepts a conclusion, so a request carrying only `conclusion` is a
 * different request from one that also carries the objective.
 */
export function updatePlaytest(
    workspace: string,
    game: string,
    playtest: string,
    input: Record<string, string | null>,
    options: MutationOptions = {},
): void {
    router.patch(
        playtests.update.url({ workspace, game, playtest }),
        { ...input },
        toVisitOptions(options),
    );
}
