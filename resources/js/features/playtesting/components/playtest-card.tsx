import { Link } from '@inertiajs/react';
import { CalendarDays, GitBranch, Layers } from 'lucide-react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import playtests from '@/routes/playtests';
import type { PlaytestSummary } from '../types/playtest';
import PlaytestStatusBadge from './playtest-status-badge';

type PlaytestCardProps = {
    playtest: PlaytestSummary;
    workspace: string;
    game: string;
};

/**
 * One playtest in a list.
 *
 * Shows the hypothesis rather than the objective, which is the one editorial
 * decision on this screen. A list is scanned for "what were we trying to find
 * out?", and a hypothesis is a sentence while an objective is usually a
 * paragraph — so the sharper one goes on the card and the full statement waits
 * on the playtest's own page.
 */
export default function PlaytestCard({
    playtest,
    workspace,
    game,
}: PlaytestCardProps) {
    const { t, choice } = useTranslation();
    const { formatDate } = useFormatters();

    const plannedAt = playtest.planned_at
        ? formatDate(playtest.planned_at)
        : null;

    return (
        <Card className="transition-colors hover:border-primary/40">
            <CardHeader className="gap-2">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Link
                        href={playtests.show.url({
                            workspace,
                            game,
                            playtest: playtest.id,
                        })}
                        className="min-w-0 font-medium hover:underline"
                        data-test={`playtest-link-${playtest.id}`}
                        dir="auto"
                    >
                        {playtest.title}
                    </Link>

                    <PlaytestStatusBadge
                        status={playtest.status}
                        label={playtest.status_label}
                    />
                </div>
            </CardHeader>

            <CardContent className="space-y-3">
                {playtest.hypothesis && (
                    <p className="line-clamp-2 text-sm text-muted-foreground">
                        <span className="font-medium text-foreground">
                            {t('Hypothesis:')}{' '}
                        </span>
                        {playtest.hypothesis}
                    </p>
                )}

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    {playtest.version_label && (
                        <span className="inline-flex items-center gap-1">
                            <GitBranch className="size-3" />
                            {playtest.version_label}
                        </span>
                    )}

                    <span className="inline-flex items-center gap-1">
                        <Layers className="size-3" />
                        {choice(
                            ':count session|:count sessions',
                            playtest.sessions_count ?? 0,
                        )}
                    </span>

                    {plannedAt && (
                        <span className="inline-flex items-center gap-1">
                            <CalendarDays className="size-3" />
                            {plannedAt}
                        </span>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
