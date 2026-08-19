import { Gauge, RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { analyseBalanceProfile } from '../api';
import type { ProfileScope } from '../hooks/use-balance-scope';
import type {
    BalanceSummary,
    BalanceWarning,
    ConversionRatio,
} from '../types/game-economy';
import BalanceWarningList from './balance-warning-list';

type BalanceAnalysisProps = {
    summary: BalanceSummary;
    errors: BalanceWarning[];
    advisories: BalanceWarning[];
    conversions: ConversionRatio[];
    scope: ProfileScope;
};

/**
 * The headline figures, the findings and the exchange rates the economy implies.
 *
 * Errors and warnings are drawn as two lists rather than one sorted one, because a designer acts on them
 * differently: an error means the economy cannot work as configured, and one of those is worth more
 * attention than a dozen warnings about shapes they may well have meant.
 *
 * The "Analyse" button does not change the numbers on this screen — they were already computed when the page
 * loaded. It exists because pressing it is a fact about how a studio works, and that fact is worth
 * publishing where a page refresh is not.
 */
export default function BalanceAnalysis({
    summary,
    errors,
    advisories,
    conversions,
    scope,
}: BalanceAnalysisProps) {
    const { t } = useTranslation();
    const [analysing, setAnalysing] = useState(false);

    const run = () => {
        setAnalysing(true);

        analyseBalanceProfile(scope, { onFinish: () => setAnalysing(false) });
    };

    const figures: { label: string; value: number; tone?: 'error' }[] = [
        { label: t('Resources'), value: summary.resources },
        { label: t('Flows'), value: summary.flows },
        { label: t('Actions'), value: summary.actions },
        { label: t('Variables'), value: summary.variables },
        { label: t('Warnings'), value: summary.warnings },
        { label: t('Errors'), value: summary.errors, tone: 'error' },
    ];

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="inline-flex items-center gap-2 text-lg font-semibold">
                    <Gauge className="size-4" />
                    {t('Analysis')}
                </h2>

                <Button
                    size="sm"
                    variant="outline"
                    onClick={run}
                    disabled={analysing}
                    data-test="analyse-button"
                >
                    {analysing ? <Spinner /> : <RefreshCw className="size-4" />}
                    {t('Analyse')}
                </Button>
            </div>

            <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                {figures.map((figure) => (
                    <Card key={figure.label}>
                        <CardContent className="p-4">
                            <dt className="text-xs text-muted-foreground">
                                {figure.label}
                            </dt>

                            <dd
                                className={
                                    figure.tone === 'error' && figure.value > 0
                                        ? 'text-2xl font-semibold text-destructive tabular-nums'
                                        : 'text-2xl font-semibold tabular-nums'
                                }
                                data-test={`summary-${figure.label.toLowerCase()}`}
                            >
                                {figure.value}
                            </dd>
                        </CardContent>
                    </Card>
                ))}
            </dl>

            <BalanceWarningList
                warnings={errors}
                title={t('Errors')}
                empty={t('Nothing here cannot work.')}
            />

            <BalanceWarningList
                warnings={advisories}
                title={t('Warnings')}
                empty={t('Nothing worth flagging.')}
            />

            <section className="space-y-2">
                <h3 className="text-sm font-medium">{t('What buys what')}</h3>

                {conversions.length === 0 ? (
                    <p className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                        {t(
                            'No action turns one resource into another yet, so there are no rates to read.',
                        )}
                    </p>
                ) : (
                    <ul className="grid gap-2 sm:grid-cols-2">
                        {conversions.map((ratio, index) => (
                            <li
                                key={`${ratio.action_id}-${ratio.from_resource_id}-${ratio.to_resource_id}-${index}`}
                                className="flex items-center justify-between gap-3 rounded-md border p-3 text-sm"
                            >
                                <span className="min-w-0">
                                    <span className="block" dir="auto">
                                        {ratio.label}
                                    </span>

                                    <span
                                        className="block text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {ratio.action_name}
                                    </span>
                                </span>

                                <span className="tabular-nums" dir="ltr">
                                    {ratio.ratio ?? '—'}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}

                <p className="text-xs text-muted-foreground">
                    {t(
                        'Rates come from individual actions and are never combined. Resources have no shared value unless a designer says so.',
                    )}
                </p>
            </section>
        </div>
    );
}
