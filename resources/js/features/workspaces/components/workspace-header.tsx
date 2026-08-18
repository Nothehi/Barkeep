import { Link } from '@inertiajs/react';
import { Archive, Settings, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import members from '@/routes/workspaces/members';
import settings from '@/routes/workspaces/settings';
import { useWorkspacePermissions } from '../hooks/use-workspace-permissions';
import type { Workspace } from '../types/workspace';

type WorkspaceHeaderProps = {
    workspace: Workspace;
};

/**
 * The heading and navigation shared by every workspace screen.
 *
 * What it offers is driven by the server's permission map. Hiding the
 * settings link from a plain member is a courtesy, not a control — the
 * settings screen authorizes the request on its own.
 */
export default function WorkspaceHeader({ workspace }: WorkspaceHeaderProps) {
    const permissions = useWorkspacePermissions(workspace);
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useTranslation();

    const membersUrl = members.index(workspace.slug);
    const settingsUrl = settings.edit(workspace.slug);

    return (
        <header className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 space-y-1">
                    <div className="flex items-center gap-2">
                        <h1
                            className="truncate text-xl font-semibold tracking-tight"
                            dir="auto"
                        >
                            {workspace.name}
                        </h1>

                        {workspace.status === 'archived' && (
                            <Badge variant="secondary">
                                <Archive />
                                {t('Archived')}
                            </Badge>
                        )}
                    </div>

                    <p
                        className="truncate text-sm text-muted-foreground"
                        dir="ltr"
                    >
                        /{workspace.slug}
                    </p>
                </div>

                <nav
                    className="flex items-center gap-2"
                    aria-label={t('Workspace')}
                >
                    <Button
                        size="sm"
                        variant="ghost"
                        asChild
                        className={cn({ 'bg-muted': isCurrentUrl(membersUrl) })}
                    >
                        <Link href={membersUrl}>
                            <Users className="size-4" />
                            {t('Members')}
                        </Link>
                    </Button>

                    {permissions.canUpdate && (
                        <Button
                            size="sm"
                            variant="ghost"
                            asChild
                            className={cn({
                                'bg-muted': isCurrentUrl(settingsUrl),
                            })}
                        >
                            <Link href={settingsUrl}>
                                <Settings className="size-4" />
                                {t('Settings')}
                            </Link>
                        </Button>
                    )}
                </nav>
            </div>

            {workspace.description && (
                <p
                    className="max-w-2xl text-sm text-muted-foreground"
                    dir="auto"
                >
                    {workspace.description}
                </p>
            )}
        </header>
    );
}
