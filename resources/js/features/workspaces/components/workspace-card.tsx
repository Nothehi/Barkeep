import { Link } from '@inertiajs/react';
import { Archive, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import workspaces from '@/routes/workspaces';
import type { Workspace } from '../types/workspace';

type WorkspaceCardProps = {
    workspace: Workspace;
};

/**
 * One workspace in the workspace list.
 */
export default function WorkspaceCard({ workspace }: WorkspaceCardProps) {
    const { t, choice } = useTranslation();
    const isArchived = workspace.status === 'archived';

    return (
        <Card className="relative transition-colors hover:border-ring">
            <CardHeader>
                <div className="flex items-start justify-between gap-3">
                    <CardTitle className="min-w-0">
                        <Link
                            href={workspaces.show(workspace.slug)}
                            className="block truncate after:absolute after:inset-0"
                            dir="auto"
                        >
                            {workspace.name}
                        </Link>
                    </CardTitle>

                    {isArchived && (
                        <Badge variant="secondary" className="shrink-0">
                            <Archive />
                            {t('Archived')}
                        </Badge>
                    )}
                </div>

                <p className="truncate text-sm text-muted-foreground" dir="ltr">
                    /{workspace.slug}
                </p>
            </CardHeader>

            <CardContent className="space-y-3">
                {workspace.description && (
                    <p
                        className="line-clamp-2 text-sm text-muted-foreground"
                        dir="auto"
                    >
                        {workspace.description}
                    </p>
                )}

                {workspace.members_count !== undefined && (
                    <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                        <Users className="size-4" />
                        {choice(
                            ':count member|:count members',
                            workspace.members_count,
                        )}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
