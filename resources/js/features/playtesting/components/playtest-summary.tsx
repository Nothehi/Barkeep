import { Clock, Eye, Layers, MessageSquare, Star, Users } from 'lucide-react';
import type { ComponentType } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import type { PlaytestMetrics } from '../types/playtest';

type PlaytestSummaryProps = {
    summary: PlaytestMetrics;
};

type Figure = {
    label: string;
    value: string;
    hint?: string;
    icon: ComponentType<{ className?: string }>;
};

/**
 * What a playtest has produced so far.
 *
 * Every figure is counted on read rather than stored, so what is on screen is
 * always in step with the rows behind it.
 *
 * The em dashes matter. A playtest with no rated feedback has no average
 * rating, and drawing "0.0" would say somebody scored the game badly; a
 * playtest whose sessions never ran has no average duration, which is not a
 * duration of zero. Nothing here invents a number to fill a space.
 */
export default function PlaytestSummary({ summary }: PlaytestSummaryProps) {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();

    const figures: Figure[] = [
        {
            label: t('Sessions'),
            value: formatNumber(summary.session_count),
            hint:
                summary.session_count === 0
                    ? undefined
                    : t(':count completed', {
                          count: formatNumber(summary.completed_session_count),
                      }),
            icon: Layers,
        },
        {
            label: t('Participants'),
            value: formatNumber(summary.participant_count),
            hint:
                summary.participant_count === 0
                    ? undefined
                    : t(':count playing', {
                          count: formatNumber(summary.player_count),
                      }),
            icon: Users,
        },
        {
            label: t('Observations'),
            value: formatNumber(summary.observation_count),
            icon: Eye,
        },
        {
            label: t('Feedback'),
            value: formatNumber(summary.feedback_count),
            hint:
                summary.rated_feedback_count === 0
                    ? undefined
                    : t(':count rated', {
                          count: formatNumber(summary.rated_feedback_count),
                      }),
            icon: MessageSquare,
        },
        {
            label: t('Average rating'),
            value:
                summary.average_feedback_rating === null
                    ? '—'
                    : formatNumber(
                          Number(summary.average_feedback_rating.toFixed(1)),
                      ),
            hint:
                summary.average_feedback_rating === null
                    ? t('Nobody scored it')
                    : t('out of 5'),
            icon: Star,
        },
        {
            label: t('Average session'),
            value: summary.average_session_duration_label ?? '—',
            hint: summary.total_duration_label
                ? t(':duration in total', {
                      duration: summary.total_duration_label,
                  })
                : undefined,
            icon: Clock,
        },
    ];

    return (
        <div
            className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
            data-test="playtest-summary"
        >
            {figures.map((figure) => (
                <Card key={figure.label}>
                    <CardContent className="flex items-start gap-3">
                        <figure.icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />

                        <div className="min-w-0 space-y-0.5">
                            <p className="text-xs text-muted-foreground">
                                {figure.label}
                            </p>

                            <p className="text-lg leading-none font-semibold">
                                {figure.value}
                            </p>

                            {figure.hint && (
                                <p className="text-xs text-muted-foreground">
                                    {figure.hint}
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
