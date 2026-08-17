import { Link } from '@inertiajs/react';
import { GitBranch, Users } from 'lucide-react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import versions from '@/routes/frameworks/versions';
import type { Framework, FrameworkVersion } from '../types/framework';
import FrameworkStatusBadge from './framework-status-badge';

type VersionListProps = {
    framework: Framework;
    versions: FrameworkVersion[];
};

/**
 * The editions of one methodology, newest first.
 *
 * How many games are following an edition is shown beside it, because that is
 * what makes archiving a version a decision rather than a click: an author
 * about to retire v1 should see that eleven studios are working through it,
 * and that none of them will be moved.
 */
export default function VersionList({
    framework,
    versions: editions,
}: VersionListProps) {
    if (editions.length === 0) {
        return (
            <div
                className="flex flex-col items-center gap-2 rounded-lg border border-dashed px-6 py-12 text-center"
                data-test="version-list-empty"
            >
                <GitBranch className="size-6 text-muted-foreground" />

                <p className="text-sm font-medium">No editions yet</p>

                <p className="max-w-md text-sm text-muted-foreground">
                    An edition is where the phases, criteria and practices
                    actually live. Cut one to start writing.
                </p>
            </div>
        );
    }

    return (
        <div className="grid gap-3" data-test="version-list">
            {editions.map((edition) => (
                <Card key={edition.id}>
                    <CardHeader className="gap-2">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <Link
                                href={versions.show.url({
                                    framework: framework.slug,
                                    version: edition.version_number,
                                })}
                                className="min-w-0 font-medium hover:underline"
                                data-test={`version-link-${edition.version_number}`}
                            >
                                {edition.label}
                                {edition.name && (
                                    <span className="ml-2 font-normal text-muted-foreground">
                                        {edition.name}
                                    </span>
                                )}
                            </Link>

                            <FrameworkStatusBadge
                                status={edition.status}
                                label={edition.status_label}
                            />
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-3">
                        {edition.description && (
                            <p className="line-clamp-2 text-sm text-muted-foreground">
                                {edition.description}
                            </p>
                        )}

                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            {edition.phases_count !== undefined && (
                                <span>
                                    {edition.phases_count === 1
                                        ? '1 phase'
                                        : `${edition.phases_count} phases`}
                                </span>
                            )}

                            {edition.adoptions_count !== undefined && (
                                <span className="inline-flex items-center gap-1.5">
                                    <Users className="size-3.5" />
                                    {edition.adoptions_count === 1
                                        ? '1 game following'
                                        : `${edition.adoptions_count} games following`}
                                </span>
                            )}

                            {edition.is_editable && (
                                <span>Still being written</span>
                            )}
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
