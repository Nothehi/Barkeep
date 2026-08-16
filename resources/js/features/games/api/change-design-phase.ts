import { router } from '@inertiajs/react';
import games from '@/routes/games';
import type { DesignPhase } from '../types/game';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Record where a game has got to in the design process.
 *
 * Any phase may follow any other, in both directions: a game that drops back
 * from playtesting to prototyping is doing the normal thing.
 */
export function changeDesignPhase(
    workspace: string,
    game: string,
    designPhase: DesignPhase,
    options: MutationOptions = {},
): void {
    router.post(
        games.designPhase.url({ workspace, game }),
        { design_phase: designPhase },
        toVisitOptions(options),
    );
}
