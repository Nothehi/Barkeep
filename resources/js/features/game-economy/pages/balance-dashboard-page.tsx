import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import type { Game, GameVersion } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import BalanceAnalysis from '../components/balance-analysis';
import BalanceAssumptionList from '../components/balance-assumption-list';
import BalanceObservationList from '../components/balance-observation-list';
import BalanceProfileHeader from '../components/balance-profile-header';
import BalanceScenarioList from '../components/balance-scenario-list';
import BalanceSnapshotList from '../components/balance-snapshot-list';
import BalanceVariableTable from '../components/balance-variable-table';
import EconomyActionForm from '../components/economy-action-form';
import EconomyActionList from '../components/economy-action-list';
import ResourceFlowList from '../components/resource-flow-list';
import ResourceForm from '../components/resource-form';
import ResourceList from '../components/resource-list';
import { useProfileScope } from '../hooks/use-balance-scope';
import { useBalancePermissions } from '../hooks/use-permissions';
import type {
    BalanceAnalysis as BalanceAnalysisData,
    BalanceAssumption,
    BalanceObservation,
    BalanceOptions,
    BalanceProfile,
    BalanceScenario,
    BalanceSnapshot,
} from '../types/game-economy';

type BalanceDashboardPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    profile: { data: BalanceProfile };
    analysis: { data: BalanceAnalysisData };
    scenarios: { data: BalanceScenario[] };
    assumptions: { data: BalanceAssumption[] };
    observations: { data: BalanceObservation[] };
    snapshots: { data: BalanceSnapshot[] };
    options: BalanceOptions;
};

/**
 * The whole economy of one design state, on one screen.
 *
 * Every section here reads from the same response. That is a deliberate departure from how the iteration
 * screens work, and the reason is that these sections are not independent: the analysis is *about* the
 * resources, the actions and the variables, and a page that fetched them separately would spend part of its
 * life showing findings about a configuration it had not finished receiving.
 *
 * It is also why every write on this page comes back as a reloaded page rather than a local update. Changing
 * one variable can turn an error into a clean analysis, and five parts of this screen would otherwise hold
 * five different ideas of what the economy is.
 *
 * The order of the sections is the order a designer builds in: resources, then how they move, then what
 * moves them, then the numbers, then the hypotheticals, then what the analysis makes of all of it.
 */
export default function BalanceDashboardPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    profile: { data: profile },
    analysis: { data: analysis },
    scenarios: { data: scenarios },
    assumptions: { data: assumptions },
    observations: { data: observations },
    snapshots: { data: snapshots },
    options,
}: BalanceDashboardPageProps) {
    const { t } = useTranslation();
    const scope = useProfileScope(workspace, game, version, profile);
    const permissions = useBalancePermissions(profile);

    const resources = analysis.resources;
    const flows = analysis.flows;
    const actions = analysis.actions;
    const variables = analysis.variables;

    return (
        <>
            <Head
                title={t(':profile · Balance · :game', {
                    profile: profile.name,
                    game: game.name,
                })}
            />

            <div className="space-y-8 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <BalanceProfileHeader
                    profile={profile}
                    scope={scope}
                    versionLabel={version.label}
                />

                <BalanceAnalysis
                    summary={analysis.summary}
                    errors={analysis.errors}
                    advisories={analysis.advisories}
                    conversions={analysis.conversions}
                    scope={scope}
                />

                <section className="space-y-3">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <Heading
                            variant="small"
                            title={t('Resources')}
                            description={t('What players hold, gain and spend')}
                        />

                        {permissions.canConfigure && (
                            <ResourceForm scope={scope} options={options} />
                        )}
                    </div>

                    <ResourceList
                        resources={resources}
                        netFlows={analysis.net_flows}
                        scope={scope}
                        options={options}
                        canConfigure={permissions.canConfigure}
                    />
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Resource flow')}
                        description={t('How those resources arrive and leave')}
                    />

                    <ResourceFlowList
                        flows={flows}
                        resources={resources}
                        scope={scope}
                        options={options}
                        canConfigure={permissions.canConfigure}
                    />
                </section>

                <section className="space-y-3">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <Heading
                            variant="small"
                            title={t('Actions')}
                            description={t(
                                'What players do that moves the economy',
                            )}
                        />

                        {permissions.canConfigure && (
                            <EconomyActionForm scope={scope} />
                        )}
                    </div>

                    <EconomyActionList
                        actions={actions}
                        profitability={analysis.profitability}
                        scope={scope}
                    />
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Variables')}
                        description={t(
                            'The numbers you change between playtests',
                        )}
                    />

                    <BalanceVariableTable
                        variables={variables}
                        scope={scope}
                        options={options}
                        canConfigure={permissions.canConfigure}
                    />
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Scenarios')}
                        description={t(
                            'Situations to read the economy under, without changing it',
                        )}
                    />

                    <BalanceScenarioList
                        scenarios={scenarios}
                        variables={variables}
                        scope={scope}
                        canConfigure={permissions.canConfigure}
                    />
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Assumptions')}
                        description={t('Why the numbers are what they are')}
                    />

                    <BalanceAssumptionList
                        assumptions={assumptions}
                        scope={scope}
                        options={options}
                        canConfigure={permissions.canConfigure}
                    />
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Observations')}
                        description={t(
                            'What playing the game revealed about them',
                        )}
                    />

                    <BalanceObservationList
                        observations={observations}
                        scope={scope}
                        options={options}
                        canConfigure={permissions.canConfigure}
                    />
                </section>

                <section className="space-y-3">
                    <Heading
                        variant="small"
                        title={t('Snapshots')}
                        description={t(
                            'Frozen copies, so a playtest stays interpretable afterwards',
                        )}
                    />

                    <BalanceSnapshotList
                        snapshots={snapshots}
                        scope={scope}
                        canSnapshot={permissions.canCreateSnapshot}
                    />
                </section>
            </div>
        </>
    );
}
