import { router } from '@inertiajs/react';
import framework from '@/routes/games/framework';
import type { GameFrameworkStatus } from '../types/framework';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * The endpoint each lifecycle move is posted to.
 *
 * Named actions rather than a status field, for the usual reason: pausing and
 * completing are moves with rules, not attributes. Completed has no entry
 * because nothing moves away from it — a studio that finishes a methodology
 * and later wants back in is adopting again.
 */
const MOVES = {
    active: framework.resume,
    paused: framework.pause,
    completed: framework.complete,
} as const;

/**
 * Move a game's adoption to another point in its lifecycle.
 *
 * Only the moves the server offered on the adoption itself will be accepted.
 * The transition matrix lives in the domain, and this posts an intent to it.
 */
export function moveAdoption(
    workspace: string,
    game: string,
    status: GameFrameworkStatus,
    options: MutationOptions = {},
): void {
    router.post(
        MOVES[status].url({ workspace, game }),
        {},
        toVisitOptions(options),
    );
}
