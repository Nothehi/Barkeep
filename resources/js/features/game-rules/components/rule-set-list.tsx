import { Link } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import type { RuleScope } from '../hooks/use-rule-scope';
import type { RuleSet } from '../types/game-rules';
import { RuleSetStatusBadge } from './status-badges';

type RuleSetListProps = {
    ruleSets: RuleSet[];
    scope: RuleScope;
};

/**
 * The rule systems written for one design version.
 *
 * Usually three or four: the draft being written, the one in play, and the archived ones before it. The
 * lineage is worth showing — "cloned from" is how a studio's rule history is actually made, and a list that
 * hid it would look like four unrelated documents.
 */
export default function RuleSetList({ ruleSets, scope }: RuleSetListProps) {
    const { t } = useTranslation();
    const { formatDate } = useFormatters();

    if (ruleSets.length === 0) {
        return (
            <Card>
                <CardContent className="px-6 py-10 text-center text-sm text-muted-foreground">
                    {t('Nobody has written the rules for this version yet.')}
                </CardContent>
            </Card>
        );
    }

    return (
        <ul className="space-y-3">
            {ruleSets.map((ruleSet) => (
                <li key={ruleSet.id}>
                    <Card>
                        <CardContent className="flex flex-wrap items-center gap-4 px-6 py-4">
                            <div className="min-w-0 space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={rules.show.url({
                                            ...scope,
                                            ruleSet: ruleSet.id,
                                        })}
                                        className="font-medium hover:underline"
                                        dir="auto"
                                        data-test={`rule-set-link-${ruleSet.id}`}
                                    >
                                        {ruleSet.name}
                                    </Link>

                                    <RuleSetStatusBadge
                                        status={ruleSet.status}
                                        label={ruleSet.status_label}
                                    />

                                    {ruleSet.cloned_from_rule_set_id && (
                                        <span className="text-xs text-muted-foreground">
                                            {t('cloned')}
                                        </span>
                                    )}
                                </div>

                                {ruleSet.description && (
                                    <p
                                        className="truncate text-sm text-muted-foreground"
                                        dir="auto"
                                    >
                                        {ruleSet.description}
                                    </p>
                                )}
                            </div>

                            <dl className="ms-auto flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                <Count
                                    label={t('rules')}
                                    value={ruleSet.rules_count}
                                />
                                <Count
                                    label={t('phases')}
                                    value={ruleSet.phases_count}
                                />
                                <Count
                                    label={t('actions')}
                                    value={ruleSet.actions_count}
                                />

                                {ruleSet.created_at && (
                                    <span className="text-xs">
                                        {formatDate(ruleSet.created_at)}
                                    </span>
                                )}
                            </dl>
                        </CardContent>
                    </Card>
                </li>
            ))}
        </ul>
    );
}

function Count({ label, value }: { label: string; value?: number }) {
    if (value === undefined) {
        return null;
    }

    return (
        <span className="tabular-nums">
            <strong className="font-medium text-foreground">{value}</strong>{' '}
            {label}
        </span>
    );
}
