import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import balance from '@/routes/balance';
import type { ProfileScope } from '../hooks/use-balance-scope';
import type { ActionProfitability, EconomyAction } from '../types/game-economy';
import Amount from './amount';

type EconomyActionListProps = {
    actions: EconomyAction[];
    profitability: ActionProfitability[];
    scope: ProfileScope;
};

/**
 * The actions a configuration declares, with what each one moves.
 *
 * Each row shows the costs and the rewards as separate lists, never as one number. "Build costs 5 wood and
 * 2 stone and pays nothing" is an answer; "Build is worth -7" is a fiction that required deciding wood and
 * stone are interchangeable, and this module refuses to invent that exchange rate — so there is nowhere on
 * this screen a total could be drawn.
 *
 * A free action is marked here rather than only in the findings, because it is the shape a designer most
 * often creates by accident: an action gets added, the costs get added later, and in between it is a button
 * with no downside.
 */
export default function EconomyActionList({
    actions,
    profitability,
    scope,
}: EconomyActionListProps) {
    const { t, choice } = useTranslation();

    const economicsFor = (action: EconomyAction) =>
        profitability.find((entry) => entry.action_id === action.id);

    if (actions.length === 0) {
        return (
            <p
                className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                data-test="actions-empty"
            >
                {t(
                    'No actions yet. An action is what a player does that moves the economy.',
                )}
            </p>
        );
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2">
            {actions.map((action) => {
                const economics = economicsFor(action);
                const spends =
                    economics?.deltas.filter((d) => d.is_spend) ?? [];
                const pays = economics?.deltas.filter((d) => d.is_gain) ?? [];

                return (
                    <Card key={action.id} data-test={`action-${action.slug}`}>
                        <CardHeader className="gap-2 pb-3">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <Link
                                    href={balance.actions.show.url({
                                        ...scope,
                                        economyAction: action.id,
                                    })}
                                    className="min-w-0 font-medium hover:underline"
                                    dir="auto"
                                    data-test={`action-link-${action.slug}`}
                                >
                                    {action.name}
                                </Link>

                                {economics && !economics.has_cost && (
                                    <Badge
                                        variant="secondary"
                                        className="gap-1"
                                    >
                                        <AlertTriangle className="size-3" />
                                        {t('Free')}
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-2 text-sm">
                            <div className="flex flex-wrap items-center gap-2">
                                <LineGroup
                                    label={t('Costs')}
                                    empty={t('nothing')}
                                    entries={spends.map((delta) => ({
                                        id: delta.resource_id,
                                        name: delta.resource_name,
                                        amount: delta.cost,
                                        unit: delta.unit,
                                    }))}
                                />

                                <ArrowRight className="size-3 shrink-0 text-muted-foreground rtl:rotate-180" />

                                <LineGroup
                                    label={t('Pays')}
                                    empty={t('nothing')}
                                    entries={pays.map((delta) => ({
                                        id: delta.resource_id,
                                        name: delta.resource_name,
                                        amount: delta.reward,
                                        unit: delta.unit,
                                    }))}
                                />
                            </div>

                            {(economics?.effect_count ?? 0) > 0 && (
                                <p className="text-xs text-muted-foreground">
                                    {choice(
                                        ':count effect|:count effects',
                                        economics?.effect_count ?? 0,
                                    )}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}

function LineGroup({
    label,
    empty,
    entries,
}: {
    label: string;
    empty: string;
    entries: {
        id: string;
        name: string;
        amount: string;
        unit: string | null;
    }[];
}) {
    return (
        <span className="min-w-0">
            <span className="block text-xs text-muted-foreground">{label}</span>

            {entries.length === 0 ? (
                <span className="text-muted-foreground">{empty}</span>
            ) : (
                <span className="flex flex-wrap gap-x-2">
                    {entries.map((entry) => (
                        <span key={entry.id}>
                            <Amount value={entry.amount} />{' '}
                            <span dir="auto">{entry.name}</span>
                        </span>
                    ))}
                </span>
            )}
        </span>
    );
}
