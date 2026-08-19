import { TriangleAlert } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import type { IterationSummary as IterationSummaryData } from '../types/prototype-iteration';
import { IterationOutcomeBadge } from './status-badges';

type IterationSummaryProps = {
    summary: IterationSummaryData;
};

/**
 * What a design cycle produced, as figures.
 *
 * The pairs are the point of this panel. Experiments against completed ones shows a cycle that closed with
 * questions still open; decisions against accepted ones shows a cycle that proposed conclusions and agreed
 * none; playtests against observations shows evidence that was attached but never produced anything. A single
 * number for each would hide all three.
 *
 * The warning about unfinished experiments is the one piece of judgement on the screen, and it is worded as
 * an observation rather than a reprimand — this module refuses to complete an experiment on the cycle's
 * behalf, so the gap is real and worth pointing out before somebody closes the cycle over the top of it.
 *
 * Nothing here is stored. Every figure is counted at the moment of the request, so it cannot disagree with
 * the rows it describes.
 */
export default function IterationSummary({ summary }: IterationSummaryProps) {
    const { t } = useTranslation();

    const figures: { label: string; value: string; test: string }[] = [
        {
            label: t('Changes'),
            value: String(summary.changes),
            test: 'summary-changes',
        },
        {
            label: t('Experiments'),
            value: `${summary.completed_experiments}/${summary.experiments}`,
            test: 'summary-experiments',
        },
        {
            label: t('Decisions'),
            value: `${summary.accepted_decisions}/${summary.decisions}`,
            test: 'summary-decisions',
        },
        {
            label: t('Playtests'),
            value: String(summary.playtests),
            test: 'summary-playtests',
        },
        {
            label: t('Observations'),
            value: String(summary.observations),
            test: 'summary-observations',
        },
        {
            label: t('Feedback'),
            value: String(summary.feedback),
            test: 'summary-feedback',
        },
    ];

    return (
        <Card data-test="iteration-summary">
            <CardHeader className="gap-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="text-base">
                        {t('What this iteration produced')}
                    </CardTitle>

                    {summary.outcome && (
                        <IterationOutcomeBadge
                            outcome={summary.outcome}
                            label={summary.outcome_label}
                        />
                    )}
                </div>
            </CardHeader>

            <CardContent className="space-y-4">
                <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {figures.map((figure) => (
                        <div
                            key={figure.label}
                            className="rounded-md border p-3"
                        >
                            <dt className="text-xs text-muted-foreground">
                                {figure.label}
                            </dt>
                            <dd
                                className="mt-1 text-lg font-semibold tabular-nums"
                                data-test={figure.test}
                            >
                                {figure.value}
                            </dd>
                        </div>
                    ))}
                </dl>

                {!summary.experiments_settled && (
                    <p
                        className="flex items-start gap-2 rounded-md border border-dashed p-3 text-xs text-muted-foreground"
                        data-test="unsettled-experiments-warning"
                    >
                        <TriangleAlert className="mt-0.5 size-3.5 shrink-0" />
                        {t(
                            'Some experiments have not been answered yet. Completing the iteration will not answer them — record their results first, or cancel the ones you dropped.',
                        )}
                    </p>
                )}

                {summary.summary && (
                    <div className="space-y-1">
                        <p className="text-xs font-medium text-muted-foreground">
                            {t('What we learned')}
                        </p>
                        <p className="text-sm" dir="auto">
                            {summary.summary}
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
