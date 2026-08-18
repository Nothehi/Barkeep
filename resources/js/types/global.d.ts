import type { WorkspaceNavigation } from '@/features/workspaces/types/workspace';
import type { LocaleState, TranslationCatalogue } from '@/lib/i18n';
import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;

            /**
             * Null for guests: the Workspace module only shares this once
             * there is an account whose memberships it can scope to.
             */
            workspaces: WorkspaceNavigation | null;

            sidebarOpen: boolean;

            /**
             * The active language, its direction, and the ones on offer.
             */
            locale: LocaleState;

            /**
             * Sent as a once prop, so it arrives on the first visit of a
             * locale and is replayed by the client from then on.
             */
            translations: TranslationCatalogue;

            [key: string]: unknown;
        };
    }
}
