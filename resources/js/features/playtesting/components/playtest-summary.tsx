import { Clock, Eye, Layers, MessageSquare, Star, Users } from 'lucide-react';
import type { ComponentType } from 'react';
import { Card, CardContent } from '@/components/ui/card';
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
    const figures: Figure[] = [
        {
            label: 'Sessions',
            value: summary.session_count.toString(),
            hint:
                summary.session_count === 0
                    ? undefined
                    : `${summary.completed_session_count} completed`,
            icon: Layers,
        },
        {
            label: 'Participants',
            value: summary.participant_count.toString(),
            hint:
                summary.participant_count === 0
                    ? undefined
                    : `${summary.player_count} playing`,
            icon: Users,
        },
        {
            label: 'Observations',
            value: summary.observation_count.toString(),
            icon: Eye,
        },
        {
            label: 'Feedback',
            value: summary.feedback_count.toString(),
            hint:
                summary.rated_feedback_count === 0
                    ? undefined
                    : `${summary.rated_feedback_count} rated`,
            icon: MessageSquare,
        },
        {
            label: 'Average rating',
            value:
                summary.average_feedback_rating === null
                    ? '—'
                    : summary.average_feedback_rating.toFixed(1),
            hint:
                summary.average_feedback_rating === null
                    ? 'Nobody scored it'
                    : 'out of 5',
            icon: Star,
        },
        {
            label: 'Average session',
            value: summary.average_session_duration_label ?? '—',
            hint: summary.total_duration_label
                ? `${summary.total_duration_label} in total`
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
