import { AlertCircle, AlertTriangle, Info } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import type { BalanceWarning } from '../types/game-economy';
import { WarningSeverityBadge } from './status-badges';

type BalanceWarningListProps = {
    warnings: BalanceWarning[];
    title: string;
    empty: string;
};

/**
 * What the analysis found, listed.
 *
 * Each finding shows the sentence about *this* configuration and, underneath, the reason the check exists at
 * all. Both come from the server: the first is built per finding, the second belongs to the code and is the
 * same wherever it appears. Keeping them apart is what lets a designer learn a check once instead of reading
 * a paragraph every time it fires.
 *
 * Nothing here offers to fix anything. The analysis reports and the designer decides — a half-built economy
 * is full of these, and a list that nagged would be a list somebody turns off.
 */
export default function BalanceWarningList({
    warnings,
    title,
    empty,
}: BalanceWarningListProps) {
    const { t } = useTranslation();

    const icons = {
        info: Info,
        warning: AlertTriangle,
        error: AlertCircle,
    };

    return (
        <section className="space-y-2">
            <h3 className="text-sm font-medium">{title}</h3>

            {warnings.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                    data-test="warnings-empty"
                >
                    {empty}
                </p>
            ) : (
                <ul className="space-y-2">
                    {warnings.map((warning, index) => {
                        const Icon = icons[warning.severity];

                        return (
                            <li
                                key={`${warning.code}-${warning.entity_id ?? index}`}
                                className="rounded-md border p-3"
                                data-test={`warning-${warning.code}`}
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <Icon
                                        className={
                                            warning.is_error
                                                ? 'size-4 text-destructive'
                                                : 'size-4 text-muted-foreground'
                                        }
                                    />

                                    <span className="font-medium">
                                        {warning.title}
                                    </span>

                                    <WarningSeverityBadge
                                        severity={warning.severity}
                                        label={warning.severity_label}
                                    />

                                    <span className="text-xs text-muted-foreground">
                                        {warning.entity_type_label}
                                        {' · '}
                                        <span dir="auto">
                                            {warning.subject}
                                        </span>
                                    </span>
                                </div>

                                <p className="mt-1 text-sm" dir="auto">
                                    {warning.description}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    {warning.explanation}
                                </p>
                            </li>
                        );
                    })}
                </ul>
            )}

            <p className="sr-only">
                {t('Findings are reported, never applied.')}
            </p>
        </section>
    );
}
