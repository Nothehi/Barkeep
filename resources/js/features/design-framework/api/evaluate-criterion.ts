import { router } from '@inertiajs/react';
import criteria from '@/routes/games/framework/criteria';
import type { CriterionRating } from '../types/framework';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Record how this game measures up against one criterion.
 *
 * Re-assessing overwrites: a criterion asks how the design is now, so there
 * is one standing answer rather than a pile of them. `not_evaluated` is not
 * sendable and the type reflects that — it is the state before anybody acts,
 * and offering it would make clearing an assessment look like making one.
 */
export function evaluateCriterion(
    workspace: string,
    game: string,
    criterion: string,
    rating: Exclude<CriterionRating, 'not_evaluated'>,
    notes: string | null = null,
    options: MutationOptions = {},
): void {
    router.post(
        criteria.evaluate.url({ workspace, game, criterion }),
        { status: rating, notes },
        toVisitOptions(options),
    );
}
