import { router } from '@inertiajs/react';
import playtests from '@/routes/playtests';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Close a playtest as answered.
 *
 * The conclusion is optional and writable afterwards, so somebody can close
 * the investigation now and write it up at the weekend.
 */
export function completePlaytest(
    workspace: string,
    game: string,
    playtest: string,
    conclusion: string,
    options: MutationOptions = {},
): void {
    router.post(
        playtests.complete.url({ workspace, game, playtest }),
        { conclusion },
        toVisitOptions(options),
    );
}
