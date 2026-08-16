import type { VisitOptions } from '@inertiajs/core';

/**
 * The shared options every playtesting mutation accepts.
 *
 * Playtesting mutations are Inertia visits rather than JSON calls. The server
 * answers each one with a redirect and, where it is worth saying, a flash
 * message — so a visit gets the new page, the updated shared props and the
 * toast in a single round trip, where a JSON call would leave the page holding
 * data it has to reconcile itself.
 *
 * That round trip is also what keeps the live session screen correct: an
 * observation added mid-session comes back as part of the reloaded session
 * rather than being spliced into a local list that could drift from what the
 * server actually stored.
 *
 * Reads are the other way round and go over the JSON API; see `./client`.
 */
export type MutationOptions = {
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;

    /**
     * Keep the scroll position. Defaulted on, because almost every action in
     * this module is pressed from part way down a growing session screen and
     * losing your place after each note would make it unusable.
     */
    preserveScroll?: boolean;
};

/**
 * Translate the options above into an Inertia visit.
 */
export function toVisitOptions(options: MutationOptions): VisitOptions {
    return {
        preserveScroll: options.preserveScroll ?? true,
        onSuccess: () => options.onSuccess?.(),
        onError: (errors) =>
            options.onError?.(errors as Record<string, string>),
        onFinish: () => options.onFinish?.(),
    };
}
