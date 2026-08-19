import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import balance from '@/routes/balance';
import { deleteEconomyAction } from '../api';
import ActionCostEditor from '../components/action-cost-editor';
import ActionEffectEditor from '../components/action-effect-editor';
import ActionRewardEditor from '../components/action-reward-editor';
import EconomyActionForm from '../components/economy-action-form';
import { useProfileScope } from '../hooks/use-balance-scope';
import { useBalancePermissions } from '../hooks/use-permissions';
import type {
    ActionProfitability,
    BalanceOptions,
    BalanceProfile,
    ConversionRatio,
    EconomyAction,
    ResourceType,
} from '../types/game-economy';

type EconomyActionPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    profile: { data: BalanceProfile };
    action: { data: EconomyAction };
    resources: { data: ResourceType[] };
    profitability: { data: ActionProfitability };
    conversions: { data: ConversionRatio[] };
    options: BalanceOptions;
};

/**
 * One action: what it takes, what it gives back, and what else it does.
 *
 * The costs and the rewards are drawn side by side and never summed. "Build costs 5 wood and 2 stone and
 * pays nothing" is an answer; "Build is worth -7" is a fiction that required deciding wood and stone are
 * interchangeable, and there is nowhere on this page such a number could appear.
 *
 * The exchange rates underneath are the exception, and they are honest because each one names the action it
 * came from: "2 wood → 1 gold, through Trade" is a fact about this game, where a rate composed across
 * several actions would be the platform inventing the most consequential number in the design.
 */
export default function EconomyActionPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    profile: { data: profile },
    action: { data: action },
    resources: { data: resources },
    profitability: { data: profitability },
    conversions: { data: conversions },
    options,
}: EconomyActionPageProps) {
    const { t } = useTranslation();
    const scope = useProfileScope(workspace, game, version, profile);
    const permissions = useBalancePermissions(profile);

    return (
        <>
            <Head
                title={t(':action · Balance · :game', {
                    action: action.name,
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
                                {action.name}
                            </h1>

                            <code
                                className="rounded bg-muted px-1.5 py-0.5 text-xs"
                                dir="ltr"
                            >
                                {action.slug}
                            </code>
                        </div>

                        {action.description && (
                            <p
                                className="max-w-2xl text-sm text-muted-foreground"
                                dir="auto"
                            >
                                {action.description}
                            </p>
                        )}

                        {!profitability.has_cost && (
                            <p className="text-sm text-muted-foreground">
                                {t(
                                    'This action costs nothing, so there is no reason not to take it every turn.',
                                )}
                            </p>
                        )}
                    </div>

                    {permissions.canConfigure && (
                        <div className="flex items-center gap-2">
                            <EconomyActionForm
                                scope={scope}
                                action={action}
                                trigger={
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        data-test="edit-action-button"
                                    >
                                        {t('Edit action')}
                                    </Button>
                                }
                            />

                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() =>
                                    deleteEconomyAction({
                                        ...scope,
                                        economyAction: action.id,
                                    })
                                }
                                data-test="delete-action-button"
                            >
                                <Trash2 className="size-3" />
                                {t('Remove')}
                            </Button>
                        </div>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title={t('Costs')}
                            description={t('What performing it takes')}
                        />

                        <ActionCostEditor
                            costs={action.costs ?? []}
                            resources={resources}
                            scope={scope}
                            economyAction={action.id}
                            canConfigure={permissions.canConfigure}
                        />
                    </section>

                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title={t('Rewards')}
                            description={t('What it pays out')}
                        />

                        <ActionRewardEditor
                            rewards={action.rewards ?? []}
                            resources={resources}
                            scope={scope}
                            economyAction={action.id}
                            canConfigure={permissions.canConfigure}
                        />
                    </section>
                </div>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Effects')}
                        description={t(
                            'What it does that is not a quantity of a resource',
                        )}
                    />

                    <ActionEffectEditor
                        effects={action.effects ?? []}
                        options={options}
                        scope={scope}
                        economyAction={action.id}
                        canConfigure={permissions.canConfigure}
                    />
                </section>

                {conversions.length > 0 && (
                    <section className="space-y-3">
                        <Heading
                            variant="small"
                            title={t('What this action buys')}
                            description={t(
                                'Read from this action alone, never combined with others',
                            )}
                        />

                        <ul className="grid gap-2 sm:grid-cols-2">
                            {conversions.map((ratio, index) => (
                                <li
                                    key={`${ratio.from_resource_id}-${ratio.to_resource_id}-${index}`}
                                    className="flex items-center justify-between gap-3 rounded-md border p-3 text-sm"
                                >
                                    <span dir="auto">{ratio.label}</span>

                                    <span className="tabular-nums" dir="ltr">
                                        {ratio.ratio ?? '—'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
