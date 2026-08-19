import { Link } from '@inertiajs/react';
import { AlertTriangle, Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import balance from '@/routes/balance';
import { deleteResource } from '../api';
import type { ProfileScope } from '../hooks/use-balance-scope';
import type {
    BalanceOptions,
    ResourceNetFlow,
    ResourceType,
} from '../types/game-economy';
import Amount, { toneForNet } from './amount';
import ResourceForm from './resource-form';

type ResourceListProps = {
    resources: ResourceType[];
    netFlows: ResourceNetFlow[];
    scope: ProfileScope;
    options: BalanceOptions;
    canConfigure: boolean;
};

/**
 * The resources a configuration declares, with what each one does on balance.
 *
 * The three figures beside every resource are the point of this list. Generation and consumption are shown
 * next to the net rather than behind it, because 12-in-8-out and 2-in-0-out both net +4 and are completely
 * different games — a designer scanning this needs to see which one they have.
 *
 * A resource with no source is marked here as well as in the findings. It is the single most consequential
 * shape in an economy — nothing produces it, so nobody can ever spend it — and burying it in a list further
 * down the page would mean somebody adds four actions priced in it before noticing.
 */
export default function ResourceList({
    resources,
    netFlows,
    scope,
    options,
    canConfigure,
}: ResourceListProps) {
    const { t } = useTranslation();

    const flowFor = (resource: ResourceType) =>
        netFlows.find((flow) => flow.resource_id === resource.id);

    if (resources.length === 0) {
        return (
            <p
                className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                data-test="resources-empty"
            >
                {t(
                    'No resources yet. Start with what players hold and spend — wood, gold, action points.',
                )}
            </p>
        );
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            {resources.map((resource) => {
                const flow = flowFor(resource);

                return (
                    <Card
                        key={resource.id}
                        data-test={`resource-${resource.slug}`}
                    >
                        <CardHeader className="gap-2 pb-3">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <Link
                                    href={balance.resources.show.url({
                                        ...scope,
                                        resourceType: resource.id,
                                    })}
                                    className="min-w-0 font-medium hover:underline"
                                    dir="auto"
                                    data-test={`resource-link-${resource.slug}`}
                                >
                                    {resource.name}
                                </Link>

                                <Badge variant="outline">
                                    {resource.category_label}
                                </Badge>
                            </div>

                            {flow && !flow.has_generation && (
                                <p className="inline-flex items-center gap-1 text-xs text-destructive">
                                    <AlertTriangle className="size-3" />
                                    {t('Nothing produces this')}
                                </p>
                            )}
                        </CardHeader>

                        <CardContent className="space-y-3">
                            {flow && (
                                <dl className="grid grid-cols-3 gap-2 text-sm">
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            {t('In')}
                                        </dt>
                                        <dd>
                                            <Amount
                                                value={flow.generation}
                                                tone="positive"
                                            />
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            {t('Out')}
                                        </dt>
                                        <dd>
                                            <Amount
                                                value={flow.consumption}
                                                tone="negative"
                                            />
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            {t('Net')}
                                        </dt>
                                        <dd>
                                            <Amount
                                                value={flow.net}
                                                signed
                                                tone={toneForNet(flow.net)}
                                            />
                                        </dd>
                                    </div>
                                </dl>
                            )}

                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                {resource.starting_value !== null && (
                                    <span>
                                        {t('Starts at')}{' '}
                                        <Amount
                                            value={resource.starting_value}
                                            unit={resource.unit}
                                        />
                                    </span>
                                )}

                                <span>
                                    {resource.max_value === null
                                        ? t('No maximum')
                                        : t('Max :amount', {
                                              amount: resource.max_value,
                                          })}
                                </span>
                            </div>

                            {canConfigure && (
                                <div className="flex items-center gap-2">
                                    <ResourceForm
                                        scope={scope}
                                        options={options}
                                        resource={resource}
                                        trigger={
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                data-test={`edit-resource-${resource.slug}`}
                                            >
                                                <Pencil className="size-3" />
                                                {t('Edit')}
                                            </Button>
                                        }
                                    />

                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            deleteResource(scope, resource.id)
                                        }
                                        data-test={`delete-resource-${resource.slug}`}
                                    >
                                        <Trash2 className="size-3" />
                                        {t('Remove')}
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
