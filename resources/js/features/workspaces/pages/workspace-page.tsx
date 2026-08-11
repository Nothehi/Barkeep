import { Head, Link } from '@inertiajs/react';
import { Settings, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import members from '@/routes/workspaces/members';
import settings from '@/routes/workspaces/settings';
import WorkspaceHeader from '../components/workspace-header';
import { useWorkspacePermissions } from '../hooks/use-workspace-permissions';
import type { Workspace } from '../types/workspace';

type WorkspacePageProps = {
    workspace: { data: Workspace };
};

/**
 * A workspace's home screen.
 *
 * Deliberately sparse: what lives inside a workspace — games, playtests,
 * content — belongs to bounded contexts that do not exist yet.
 */
export default function WorkspacePage({
    workspace: { data: workspace },
}: WorkspacePageProps) {
    const permissions = useWorkspacePermissions(workspace);

    return (
        <>
            <Head title={workspace.name} />

            <div className="space-y-6 px-4 py-6">
                <WorkspaceHeader workspace={workspace} />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Members</CardTitle>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                {workspace.members_count ?? 0} in this
                                workspace.
                            </p>

                            <Button variant="outline" asChild>
                                <Link href={members.index(workspace.slug)}>
                                    <Users className="size-4" />
                                    View members
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    {permissions.canUpdate && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Settings</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    Rename the workspace, change its address or
                                    retire it.
                                </p>

                                <Button variant="outline" asChild>
                                    <Link href={settings.edit(workspace.slug)}>
                                        <Settings className="size-4" />
                                        Open settings
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}
