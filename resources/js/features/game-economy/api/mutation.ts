import type { VisitOptions } from '@inertiajs/core';

/**
 * The shared options every balance mutation accepts.
 *
 * These mutations are Inertia visits rather than JSON calls. The server answers each one with a redirect
 * and, where it is worth saying, a flash message — so a visit gets the new page, the updated shared props
 * and the toast in a single round trip, where a JSON call would leave the page holding data it has to
 * reconcile itself.
 *
 * That round trip matters more here than almost anywhere else in the application. The balance dashboard
 * shows the same configuration five ways at once — the summary counts, the resource list with its net
 * flows, the actions, the variable table and the findings — and *every* write moves all five. Changing one
 * variable can turn an error into a clean analysis. Splicing the new value into a local array would leave
 * five parts of one screen holding five different ideas of what the economy is.
 *
 * Reads are the other way round and go over the JSON API; see `./client`.
 */
export type MutationOptions = {
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;

    /**
     * Keep the scroll position. Defaulted on, because almost every action in this module is pressed from
     * part way down a long dashboard and losing your place after each entry would make it unusable.
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
