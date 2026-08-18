import { Head, Link } from '@inertiajs/react';
import { Gamepad2, Settings, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import games from '@/routes/games';
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
 * Still sparse: games are the only thing that lives inside a workspace so
 * far, and playtests and content belong to bounded contexts that do not exist
 * yet.
 *
 * The games card is offered unconditionally because seeing the games in a
 * workspace asks exactly what seeing the workspace does — membership, and a
 * status that is still readable. Anybody rendering this screen has already
 * passed that gate.
 */
export default function WorkspacePage({
    workspace: { data: workspace },
}: WorkspacePageProps) {
    const permissions = useWorkspacePermissions(workspace);
    const { t, choice } = useTranslation();

    return (
        <>
            <Head title={workspace.name} />

            <div className="space-y-6 px-4 py-6">
                <WorkspaceHeader workspace={workspace} />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Games')}</CardTitle>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                {t('The board games being designed here.')}
                            </p>

                            <Button variant="outline" asChild>
                                <Link href={games.index(workspace.slug)}>
                                    <Gamepad2 className="size-4" />
                                    {t('View games')}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Members')}</CardTitle>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                {choice(
                                    ':count person in this workspace.|:count people in this workspace.',
                                    workspace.members_count ?? 0,
                                )}
                            </p>

                            <Button variant="outline" asChild>
                                <Link href={members.index(workspace.slug)}>
                                    <Users className="size-4" />
                                    {t('View members')}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    {permissions.canUpdate && (
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Settings')}</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'Rename the workspace, change its address or retire it.',
                                    )}
                                </p>

                                <Button variant="outline" asChild>
                                    <Link href={settings.edit(workspace.slug)}>
                                        <Settings className="size-4" />
                                        {t('Open settings')}
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
