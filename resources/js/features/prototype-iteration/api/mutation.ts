import type { VisitOptions } from '@inertiajs/core';

/**
 * The shared options every prototype and iteration mutation accepts.
 *
 * These mutations are Inertia visits rather than JSON calls. The server answers each one with a redirect and,
 * where it is worth saying, a flash message — so a visit gets the new page, the updated shared props and the
 * toast in a single round trip, where a JSON call would leave the page holding data it has to reconcile
 * itself.
 *
 * That round trip matters more here than almost anywhere else in the application. The iteration screen shows
 * the same cycle four ways at once — a header with counts, a list of changes, a summary panel and a timeline
 * — and every one of them moves when a change is recorded. Splicing the new change into a local array would
 * leave four parts of one screen holding four different ideas of what the cycle contains.
 *
 * Reads are the other way round and go over the JSON API; see `./client`.
 */
export type MutationOptions = {
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;

    /**
     * Keep the scroll position. Defaulted on, because almost every action in this module is pressed from part
     * way down a long iteration screen and losing your place after each entry would make it unusable.
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
