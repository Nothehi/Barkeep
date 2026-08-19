import { ArrowDown } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import type {
    EconomyAction,
    ResourceFlow,
    ResourceNetFlow,
    ResourceType,
} from '../types/game-economy';
import Amount, { toneForNet } from './amount';

type ResourceFlowDiagramProps = {
    resource: ResourceType;
    flows: ResourceFlow[];
    actions: EconomyAction[];
    netFlow: ResourceNetFlow | null;
};

/**
 * Where one resource comes from and where it goes.
 *
 * Deliberately not a graph library. A drawn graph of an eight-resource economy is a hairball nobody can
 * read, and the question a designer actually has is narrower: *for this resource*, what fills it and what
 * empties it. That is two columns and an arrow, which CSS does perfectly well — and which stays legible on
 * a phone, in dark mode and under an RTL page, none of which a canvas would.
 *
 * Actions appear alongside declared flows because they are sources and sinks too: an action costing five
 * wood removes wood whether or not anybody wrote a consumption flow for it, and a picture that showed only
 * the flows would tell a designer their most expensive action does not exist.
 */
export default function ResourceFlowDiagram({
    resource,
    flows,
    actions,
    netFlow,
}: ResourceFlowDiagramProps) {
    const { t } = useTranslation();

    const own = flows.filter((flow) => flow.resource_type_id === resource.id);

    const sources = [
        ...own
            .filter((flow) => flow.direction > 0)
            .map((flow) => ({
                key: `flow-${flow.id}`,
                label: flow.name,
                amount: flow.amount,
                note: flow.condition,
            })),
        ...actions.flatMap((action) =>
            (action.rewards ?? [])
                .filter((line) => line.resource_type_id === resource.id)
                .map((line) => ({
                    key: `reward-${line.id}`,
                    label: action.name,
                    amount: line.amount,
                    note: t('reward'),
                })),
        ),
    ];

    const sinks = [
        ...own
            .filter((flow) => flow.direction < 0)
            .map((flow) => ({
                key: `flow-${flow.id}`,
                label: flow.name,
                amount: flow.amount,
                note: flow.condition,
            })),
        ...actions.flatMap((action) =>
            (action.costs ?? [])
                .filter((line) => line.resource_type_id === resource.id)
                .map((line) => ({
                    key: `cost-${line.id}`,
                    label: action.name,
                    amount: line.amount,
                    note: t('cost'),
                })),
        ),
    ];

    return (
        <div className="space-y-3" data-test="resource-flow-diagram">
            <Column
                title={t('What fills it')}
                empty={t('Nothing produces this resource.')}
                entries={sources}
                tone="positive"
            />

            <div className="flex justify-center text-muted-foreground">
                <ArrowDown className="size-4" />
            </div>

            <div className="rounded-md border-2 border-primary/40 bg-primary/5 px-4 py-3 text-center">
                <p className="font-medium" dir="auto">
                    {resource.name}
                </p>

                {netFlow && (
                    <p className="text-sm">
                        <Amount
                            value={netFlow.net}
                            signed
                            unit={resource.unit}
                            tone={toneForNet(netFlow.net)}
                        />
                    </p>
                )}
            </div>

            <div className="flex justify-center text-muted-foreground">
                <ArrowDown className="size-4" />
            </div>

            <Column
                title={t('What empties it')}
                empty={t('Nothing spends this resource.')}
                entries={sinks}
                tone="negative"
            />
        </div>
    );
}

type Entry = {
    key: string;
    label: string;
    amount: string;
    note?: string | null;
};

function Column({
    title,
    empty,
    entries,
    tone,
}: {
    title: string;
    empty: string;
    entries: Entry[];
    tone: 'positive' | 'negative';
}) {
    return (
        <div className="space-y-2">
            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {title}
            </p>

            {entries.length === 0 ? (
                <p className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                    {empty}
                </p>
            ) : (
                <ul className="grid gap-2 sm:grid-cols-2">
                    {entries.map((entry) => (
                        <li
                            key={entry.key}
                            className="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                        >
                            <span className="min-w-0">
                                <span className="block truncate" dir="auto">
                                    {entry.label}
                                </span>

                                {entry.note ? (
                                    <span
                                        className="block text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {entry.note}
                                    </span>
                                ) : null}
                            </span>

                            <Amount
                                value={
                                    tone === 'negative'
                                        ? `-${entry.amount}`
                                        : entry.amount
                                }
                                signed
                                tone={tone}
                            />
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
