import { usePage } from '@inertiajs/react';
import type { Workspace, WorkspaceNavigation } from '../types/workspace';

export type UseWorkspacesResult = {
    workspaces: Workspace[];
    current: Workspace | null;
    hasWorkspaces: boolean;
};

/**
 * The workspaces the signed in account can switch between.
 *
 * Reads the `workspaces` prop the Workspace module shares with every Inertia
 * response, so no screen has to fetch or cache the list itself, and the list
 * is refreshed by the same round trip that changed it.
 *
 * The list is scoped to membership on the server. It is navigation data, not
 * a permission: every request is authorized against the workspace the URL
 * resolves to, whatever the client believes is selected.
 */
export function useWorkspaces(): UseWorkspacesResult {
    const page = usePage<{ workspaces?: WorkspaceNavigation | null }>();
    const navigation = page.props.workspaces ?? null;

    const workspaces = navigation?.available ?? [];

    const current =
        workspaces.find(
            (workspace) => workspace.slug === navigation?.current,
        ) ?? null;

    return {
        workspaces,
        current,
        hasWorkspaces: workspaces.length > 0,
    };
}
