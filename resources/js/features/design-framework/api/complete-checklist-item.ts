import { router } from '@inertiajs/react';
import checklistItems from '@/routes/games/framework/checklist-items';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Tick — or untick — one requirement on a checklist.
 *
 * The item is addressed directly rather than through its list, because the
 * server resolves it through this game's adoption either way and the extra
 * segment would name something the request does not get to choose.
 */
export function completeChecklistItem(
    workspace: string,
    game: string,
    item: string,
    completed = true,
    notes: string | null = null,
    options: MutationOptions = {},
): void {
    router.post(
        checklistItems.complete.url({ workspace, game, item }),
        { completed, notes },
        toVisitOptions(options),
    );
}
