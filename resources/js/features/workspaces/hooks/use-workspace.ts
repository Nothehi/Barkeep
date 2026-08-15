import { usePage } from '@inertiajs/react';
import type { Workspace } from '../types/workspace';
import { useWorkspaces } from './use-workspaces';

/**
 * The workspace the current screen is about.
 *
 * Prefers the page's own `workspace` prop, which the workspace screens send
 * in full, and falls back to the entry in the shared switcher list so that
 * layout chrome still knows where it is on pages that do not.
 */
export function useWorkspace(): Workspace | null {
    const page = usePage<{ workspace?: { data: Workspace } | Workspace }>();
    const { current } = useWorkspaces();
    const fromPage = page.props.workspace;

    if (fromPage && 'data' in fromPage) {
        return fromPage.data;
    }

    return fromPage ?? current;
}
