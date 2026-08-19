import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import balance from '@/routes/balance';
import Amount, { toneForNet } from '../components/amount';
import ResourceFlowDiagram from '../components/resource-flow-diagram';
import ResourceFlowList from '../components/resource-flow-list';
import ResourceForm from '../components/resource-form';
import { useProfileScope } from '../hooks/use-balance-scope';
import { useBalancePermissions } from '../hooks/use-permissions';
import type {
    BalanceOptions,
    BalanceProfile,
    EconomyAction,
    ResourceFlow,
    ResourceNetFlow,
    ResourceType,
} from '../types/game-economy';

type ResourcePageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    profile: { data: BalanceProfile };
    resource: { data: ResourceType };
    flows: { data: ResourceFlow[] };
    net_flow: { data: ResourceNetFlow };
    actions: { data: EconomyAction[] };
    options: BalanceOptions;
};

/**
 * One resource: what it is, what it can do, and what moves it.
 *
 * The three figures at the top are the page. Generation and consumption sit beside the net rather than
 * behind it, because 12-in-8-out and 2-in-0-out both net +4 and describe completely different games — and
 * this is the screen somebody opens when they want to know which one they have.
 *
 * The flags are shown as badges rather than as switches, because what a resource *can do* changes rarely and
 * reads constantly. Editing them is a dialog away.
 */
export default function ResourcePage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    profile: { data: profile },
    resource: { data: resource },
    flows: { data: flows },
    net_flow: { data: netFlow },
    actions: { data: actions },
    options,
}: ResourcePageProps) {
    const { t } = useTranslation();
    const scope = useProfileScope(workspace, game, version, profile);
    const permissions = useBalancePermissions(profile);

    const capabilities = [
        { label: t('Spendable'), on: resource.is_spendable },
        { label: t('Accumulative'), on: resource.is_accumulative },
        { label: t('Tradeable'), on: resource.is_tradeable },
        { label: t('Convertible'), on: resource.is_convertible },
    ];

    return (
        <>
            <Head
                title={t(':resource · Balance · :game', {
                    resource: resource.name,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={balance.show.url(scope)}>
                        <ArrowLeft className="size-4 rtl:rotate-180" />
                        {profile.name}
                    </Link>
                </Button>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold" dir="auto">
                                {resource.name}
                            </h1>

                            <Badge variant="outline">
                                {resource.category_label}
                            </Badge>

                            <code
                                className="rounded bg-muted px-1.5 py-0.5 text-xs"
                                dir="ltr"
                            >
                                {resource.slug}
                            </code>
                        </div>

                        {resource.description && (
                            <p
                                className="max-w-2xl text-sm text-muted-foreground"
                                dir="auto"
                            >
                                {resource.description}
                            </p>
                        )}
                    </div>

                    {permissions.canConfigure && (
                        <ResourceForm
                            scope={scope}
                            options={options}
                            resource={resource}
                            trigger={
                                <Button
                                    size="sm"
                                    variant="outline"
                                    data-test="edit-resource-button"
                                >
                                    {t('Edit resource')}
                                </Button>
                            }
                        />
                    )}
                </div>

                <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <Figure label={t('In')}>
                        <Amount value={netFlow.generation} tone="positive" />
                    </Figure>

                    <Figure label={t('Out')}>
                        <Amount value={netFlow.consumption} tone="negative" />
                    </Figure>

                    <Figure label={t('Net')}>
                        <Amount
                            value={netFlow.net}
                            signed
                            tone={toneForNet(netFlow.net)}
                        />
                    </Figure>

                    <Figure label={t('Starting value')}>
                        <Amount
                            value={resource.starting_value}
                            unit={resource.unit}
                        />
                    </Figure>

                    <Figure label={t('Minimum')}>
                        <Amount value={resource.min_value} />
                    </Figure>

                    <Figure label={t('Maximum')}>
                        <Amount value={resource.max_value} />
                    </Figure>
                </dl>

                <div className="flex flex-wrap gap-2">
                    {capabilities.map((capability) => (
                        <Badge
                            key={capability.label}
                            variant={capability.on ? 'secondary' : 'outline'}
                            className={
                                capability.on
                                    ? undefined
                                    : 'text-muted-foreground line-through'
                            }
                        >
                            {capability.label}
                        </Badge>
                    ))}
                </div>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Where it comes from and where it goes')}
                        description={t(
                            'Declared flows and the actions that move it, together',
                        )}
                    />

                    <ResourceFlowDiagram
                        resource={resource}
                        flows={flows}
                        actions={actions}
                        netFlow={netFlow}
                    />
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Flows')}
                        description={t(
                            'The declared ways this resource arrives and leaves',
                        )}
                    />

                    <ResourceFlowList
                        flows={flows}
                        resources={[resource]}
                        scope={scope}
                        options={options}
                        canConfigure={permissions.canConfigure}
                        resourceId={resource.id}
                    />
                </section>
            </div>
        </>
    );
}

function Figure({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <Card>
            <CardContent className="p-4">
                <dt className="text-xs text-muted-foreground">{label}</dt>
                <dd className="text-lg font-semibold">{children}</dd>
            </CardContent>
        </Card>
    );
}
