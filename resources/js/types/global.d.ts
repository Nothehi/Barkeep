import type { WorkspaceNavigation } from '@/features/workspaces/types/workspace';
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
            [key: string]: unknown;
        };
    }
}
