import { AlertTriangle, CircleAlert } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import type { RuleSetSummary } from '../types/game-rules';

type SummaryTilesProps = {
    summary: RuleSetSummary;
};

/**
 * How much of a rule system there is, across the top of the dashboard.
 *
 * Counted on the server, never assembled here. The numbers and the lists below them come from the same
 * response, which is what stops the heading saying twenty-four while the tree shows twenty-three.
 *
 * The two findings tiles are drawn last and coloured, because they are the only numbers on the row somebody
 * is meant to *act* on — the rest are there to say how much work is behind the screen.
 */
export default function SummaryTiles({ summary }: SummaryTilesProps) {
    const { t } = useTranslation();

    const counts: { label: string; value: number }[] = [
        { label: t('Rules'), value: summary.rules },
        { label: t('Mechanics'), value: summary.mechanics },
        { label: t('Phases'), value: summary.phases },
        { label: t('Actions'), value: summary.actions },
        { label: t('Conditions'), value: summary.conditions },
    ];

    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            {counts.map((count) => (
                <Card key={count.label}>
                    <CardContent className="px-4 py-3">
                        <p className="text-2xl font-semibold tabular-nums">
                            {count.value}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {count.label}
                        </p>
                    </CardContent>
                </Card>
            ))}

            <Card
                className={
                    summary.warnings > 0 ? 'border-amber-500/40' : undefined
                }
            >
                <CardContent className="px-4 py-3">
                    <p className="flex items-center gap-1.5 text-2xl font-semibold tabular-nums">
                        <AlertTriangle className="size-4 text-amber-500" />
                        {summary.warnings}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {t('Warnings')}
                    </p>
                </CardContent>
            </Card>

            <Card
                className={
                    summary.errors > 0 ? 'border-destructive/50' : undefined
                }
            >
                <CardContent className="px-4 py-3">
                    <p className="flex items-center gap-1.5 text-2xl font-semibold tabular-nums">
                        <CircleAlert className="size-4 text-destructive" />
                        {summary.errors}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {t('Errors')}
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
