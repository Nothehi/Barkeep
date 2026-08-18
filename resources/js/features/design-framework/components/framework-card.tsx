import { Link } from '@inertiajs/react';
import { Layers } from 'lucide-react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import frameworks from '@/routes/frameworks';
import type { Framework } from '../types/framework';
import FrameworkStatusBadge from './framework-status-badge';

type FrameworkCardProps = {
    framework: Framework;
};

/**
 * One methodology in the catalogue.
 *
 * The latest edition is named on the card rather than left to the detail page,
 * because a framework without one is not yet something a game can follow — and
 * "Board Game Design Framework" with no edition beside it tells a designer
 * nothing about whether there is anything here to adopt.
 */
export default function FrameworkCard({ framework }: FrameworkCardProps) {
    const { t, choice } = useTranslation();
    const latest = framework.latest_version ?? null;

    return (
        <Card className="transition-colors hover:border-primary/40">
            <CardHeader className="gap-2">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Link
                        href={frameworks.show.url({
                            framework: framework.slug,
                        })}
                        className="min-w-0 font-medium hover:underline"
                        data-test={`framework-link-${framework.slug}`}
                        dir="auto"
                    >
                        {framework.name}
                    </Link>

                    <FrameworkStatusBadge
                        status={framework.status}
                        label={framework.status_label}
                    />
                </div>
            </CardHeader>

            <CardContent className="space-y-3">
                {framework.description && (
                    <p
                        className="line-clamp-2 text-sm text-muted-foreground"
                        dir="auto"
                    >
                        {framework.description}
                    </p>
                )}

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span className="inline-flex items-center gap-1.5">
                        <Layers className="size-3.5" />
                        {latest
                            ? t('Latest :edition', { edition: latest.label })
                            : t('No editions yet')}
                    </span>

                    {framework.versions_count !== undefined && (
                        <span>
                            {choice(
                                ':count edition|:count editions',
                                framework.versions_count,
                            )}
                        </span>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
