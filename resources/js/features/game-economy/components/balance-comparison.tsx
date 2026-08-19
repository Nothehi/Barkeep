import { ArrowRight } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import type { BalanceComparison, SnapshotChange } from '../types/game-economy';
import { SnapshotChangeBadge } from './status-badges';

type BalanceComparisonProps = {
    comparison: BalanceComparison;
};

/**
 * What changed between two frozen configurations.
 *
 * Grouped by what kind of thing moved rather than flattened, because that is how the question is asked:
 * "what happened to the variables?" is a different question from "what resources did we add?", and one list
 * would make both a filtering exercise.
 *
 * "10 → 12" can be read at face value, because the direction is fixed by the request: `from` is always the
 * earlier snapshot. Both sides come as strings from the server, which is what keeps the arithmetic honest —
 * the difference between two decimals was worked out where decimals are exact.
 */
export default function BalanceComparisonView({
    comparison,
}: BalanceComparisonProps) {
    const { t } = useTranslation();

    const groups: { title: string; changes: SnapshotChange[] }[] = [
        { title: t('Resources'), changes: comparison.resources },
        { title: t('Flows'), changes: comparison.flows },
        { title: t('Actions'), changes: comparison.actions },
        { title: t('Costs'), changes: comparison.costs },
        { title: t('Rewards'), changes: comparison.rewards },
        { title: t('Effects'), changes: comparison.effects },
        { title: t('Variables'), changes: comparison.variables },
    ].filter((group) => group.changes.length > 0);

    if (comparison.is_identical) {
        return (
            <p
                className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                data-test="comparison-identical"
            >
                {t('Nothing changed between these two snapshots.')}
            </p>
        );
    }

    return (
        <div className="space-y-6" data-test="comparison">
            {groups.map((group) => (
                <section key={group.title} className="space-y-2">
                    <h3 className="text-sm font-medium text-muted-foreground">
                        {group.title}
                    </h3>

                    <ul className="divide-y rounded-md border">
                        {group.changes.map((change) => (
                            <li
                                key={`${change.entity_type}-${change.key}`}
                                className="space-y-2 p-3"
                                data-test={`change-${change.key}`}
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <SnapshotChangeBadge
                                        type={change.type}
                                        label={change.type_label}
                                    />

                                    <span className="font-medium" dir="auto">
                                        {change.label}
                                    </span>
                                </div>

                                {change.fields.length > 0 && (
                                    <ul className="space-y-1 ps-1 text-sm">
                                        {change.fields.map((field) => (
                                            <li
                                                key={field.field}
                                                className="flex flex-wrap items-center gap-2"
                                            >
                                                <span className="text-xs text-muted-foreground">
                                                    {field.label}
                                                </span>

                                                <span
                                                    className="text-muted-foreground tabular-nums"
                                                    dir="auto"
                                                >
                                                    {field.before ?? '—'}
                                                </span>

                                                <ArrowRight className="size-3 text-muted-foreground rtl:rotate-180" />

                                                <span
                                                    className="font-medium tabular-nums"
                                                    dir="auto"
                                                >
                                                    {field.after ?? '—'}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}
