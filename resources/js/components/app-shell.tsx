import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    /**
     * The shell is exactly one viewport tall so the scrolling happens inside
     * it rather than on the document. Chrome pins the document's scrollbar to
     * the right of the window whatever `dir` says, but an element's scrollbar
     * follows that element's direction — so an inner scroller is what puts the
     * bar on the reader's left in Persian.
     */
    return (
        <SidebarProvider defaultOpen={isOpen} className="h-svh">
            {children}
        </SidebarProvider>
    );
}
