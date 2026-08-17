import type { VisitOptions } from '@inertiajs/core';

/**
 * The shared options every design framework mutation accepts.
 *
 * Framework mutations are Inertia visits rather than JSON calls. The server
 * answers each one with a redirect and a flash message, so a visit gets the
 * new page, the updated shared props and the toast in a single round trip.
 *
 * There is no JSON read client alongside this, unlike the game and playtest
 * features. Every framework screen is rendered with the whole of what it
 * shows — the content, the studio's record against it, and the progress
 * counted from both — so a phase page that reloads after a tick is showing
 * what the server actually stored rather than something the client spliced
 * in. That matters on a screen a designer edits repeatedly while thinking.
 */
export type MutationOptions = {
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;

    /**
     * Keep the scroll position. On by default here, because almost every
     * framework mutation is pressed from partway down a long phase page.
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
