import { router } from '@inertiajs/react';
import mechanics from '@/routes/mechanics';
import type { MechanicCategory } from '../types/mechanic';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * What a term in the vocabulary is written from.
 *
 * No address. A mechanic's slug is derived from its name by the server and
 * made unique there, so a curator names the thing and the platform decides what
 * to call it in a URL. No status either: adding a term publishes it, and
 * retiring one is its own action with its own rule.
 */
export type MechanicInput = {
    name: string;
    description?: string | null;
    category: MechanicCategory;
};

/**
 * Add a term to the vocabulary.
 */
export function createMechanic(
    input: MechanicInput,
    options: MutationOptions = {},
): void {
    router.post(mechanics.store.url(), { ...input }, toVisitOptions(options));
}

/**
 * Change what a term is called or means.
 *
 * Worth knowing what this touches: it changes what is displayed on every game
 * that claimed the term, in every workspace. That is the intended behaviour of
 * a shared vocabulary, and the reason the server only lets a curator do it.
 */
export function updateMechanic(
    mechanic: string,
    input: MechanicInput,
    options: MutationOptions = {},
): void {
    router.patch(
        mechanics.update.url({ mechanic }),
        { ...input },
        toVisitOptions(options),
    );
}

/**
 * Retire a term.
 *
 * Not a delete, and not reversible through this call. The term stops being
 * offered and the games that already claimed it keep saying what they said.
 */
export function archiveMechanic(
    mechanic: string,
    options: MutationOptions = {},
): void {
    router.post(
        mechanics.archive.url({ mechanic }),
        {},
        toVisitOptions(options),
    );
}
