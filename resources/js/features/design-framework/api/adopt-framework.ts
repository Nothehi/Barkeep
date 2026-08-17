import { router } from '@inertiajs/react';
import framework from '@/routes/games/framework';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Point a game at an edition of a methodology.
 *
 * The only framework call that names an edition. A game adopting a framework
 * is choosing from every published edition on the platform, so there is no
 * parent segment to resolve the choice through — which is why the server
 * proves the edition is published and adoptable itself rather than trusting
 * the picker that produced the id.
 *
 * There is no counterpart that changes the edition afterwards. Moving a game
 * from v1 to v2 has real decisions in it about what happens to evaluations
 * already recorded, and the module does not pretend to make them.
 */
export function adoptFramework(
    workspace: string,
    game: string,
    frameworkVersionId: string,
    options: MutationOptions = {},
): void {
    router.post(
        framework.store.url({ workspace, game }),
        { framework_version_id: frameworkVersionId },
        toVisitOptions(options),
    );
}
