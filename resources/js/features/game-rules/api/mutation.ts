import type { VisitOptions } from '@inertiajs/core';

/**
 * The shared options every rules mutation accepts.
 *
 * These mutations are Inertia visits rather than JSON calls. The server answers each one with a redirect
 * and, where it is worth saying, a flash message — so a visit gets the new page, the updated shared props
 * and the toast in a single round trip, where a JSON call would leave the page holding data it has to
 * reconcile itself.
 *
 * That round trip matters more here than almost anywhere else in the application. The rules dashboard shows
 * the same rule set eight ways at once — the summary counts, the rule tree, the phases, the actions, the
 * conditions, the outcomes, the graph and the findings — and almost *every* write moves several of them.
 * Drawing one transition can turn an unreachable phase into a reachable one three sections further down and
 * remove two findings from the list. Splicing the new record into a local array would leave eight parts of
 * one screen holding eight different ideas of what the rule system is.
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
