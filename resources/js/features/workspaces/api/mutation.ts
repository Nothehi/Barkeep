import type { VisitOptions } from '@inertiajs/core';

/**
 * The shared options every workspace mutation accepts.
 *
 * Workspace mutations are Inertia visits rather than JSON calls. The server
 * answers each one with a redirect and a flash message, so a visit gets the
 * new page, the updated shared props and the toast in a single round trip —
 * where a JSON call would leave the page holding data it has to reconcile
 * itself.
 *
 * Reads are the other way round and go over the JSON API; see `./client`.
 */
export type MutationOptions = {
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;

    /**
     * Keep the scroll position. Worth setting for changes made from a dialog
     * halfway down a long member list.
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
