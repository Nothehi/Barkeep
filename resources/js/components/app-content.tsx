import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

const scrollRegion = { 'scroll-region': '' };

export function AppContent({ variant = 'sidebar', children, ...props }: Props) {
    if (variant === 'sidebar') {
        /**
         * `scroll-region` is how Inertia is told that this, not the document,
         * is what to reset on a visit and restore on a back button — without
         * it every page would open at wherever the last one was left.
         */
        return (
            <SidebarInset {...scrollRegion} {...props}>
                {children}
            </SidebarInset>
        );
    }

    return (
        <main
            className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
            {...props}
        >
            {children}
        </main>
    );
}
