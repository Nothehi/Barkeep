import { router } from '@inertiajs/react';
import playtests from '@/routes/playtests';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Call a playtest off.
 *
 * Its sessions are left alone: a playtest can be cancelled with two completed
 * sittings behind it, and those sittings really did happen.
 */
export function cancelPlaytest(
    workspace: string,
    game: string,
    playtest: string,
    options: MutationOptions = {},
): void {
    router.post(
        playtests.cancel.url({ workspace, game, playtest }),
        {},
        toVisitOptions(options),
    );
}
