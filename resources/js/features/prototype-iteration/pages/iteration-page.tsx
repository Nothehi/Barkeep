import { Head } from '@inertiajs/react';
import { GitBranchPlus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Game, GameVersion } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import { createNextGameVersion } from '../api';
import DecisionList from '../components/decision-list';
import DesignChangeList from '../components/design-change-list';
import ExperimentList from '../components/experiment-list';
import IterationHeader from '../components/iteration-header';
import IterationSummary from '../components/iteration-summary';
import IterationTimeline from '../components/iteration-timeline';
import RelatedPlaytests from '../components/related-playtests';
import { useIterationPermissions } from '../hooks/use-permissions';
import { emptyNextGameVersionInput } from '../schemas/prototype-iteration';
import type {
    CitedEvidence,
    DesignChange,
    DesignDecision,
    DesignExperiment,
    Iteration,
    IterationOptions,
    IterationSummary as IterationSummaryData,
    IterationTimeline as IterationTimelineData,
    PlaytestReference,
    PrototypeVersion,
    SelectablePlaytest,
} from '../types/prototype-iteration';

type IterationPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    iteration: { data: Iteration };
    changes: { data: DesignChange[] };
    experiments: { data: DesignExperiment[] };
    decisions: { data: DesignDecision[] };
    evidence: Record<string, CitedEvidence[]>;
    playtests: { data: PlaytestReference[] };
    summary: { data: IterationSummaryData };
    timeline: { data: IterationTimelineData };
    versions: { data: GameVersion[] };
    prototype_versions: { data: PrototypeVersion[] };
    available_playtests: SelectablePlaytest[];
    options: IterationOptions;
};

/**
 * One design cycle, in full.
 *
 * The page is laid out as the design loop rather than as a list of tables, which is section 40: what were we
 * trying to change, why, what did we test, what happened, what did we decide. Reading top to bottom should
 * answer those five questions in that order without anybody having to know the schema.
 *
 * The timeline sits alongside the sections rather than instead of them. They serve different readers: somebody
 * arriving to find one thing uses the sections, and somebody catching up on a cycle they were not part of uses
 * the line — where the order and the gaps between entries are the information.
 *
 * Everything arrives in one response rather than being fetched section by section. A design cycle is read as a
 * whole, and a page that filled in piecemeal would be unreadable for the second it took.
 *
 * The evidence for each decision is keyed here rather than nested in the decision resource, because resolving
 * a citation means reading live from Playtesting and that belongs in a query rather than in a resource.
 */
export default function IterationPage({
    workspace: { data: workspace },
    game: { data: game },
    iteration: { data: iteration },
    changes: { data: changes },
    experiments: { data: experiments },
    decisions: { data: decisions },
    evidence,
    playtests: { data: playtests },
    summary: { data: summary },
    timeline: { data: timeline },
    prototype_versions: { data: prototypeVersions },
    available_playtests: availablePlaytests,
    options,
}: IterationPageProps) {
    const { t } = useTranslation();
    const permissions = useIterationPermissions(iteration);

    return (
        <>
            <Head
                title={t(':iteration · :game', {
                    iteration: iteration.title,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <IterationHeader
                    iteration={iteration}
                    workspace={workspace.slug}
                    game={game.slug}
                    options={options}
                />

                <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                    <div className="space-y-6">
                        <Card data-test="iteration-objective">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    {t('What we were trying to change')}
                                </CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-3">
                                <p className="text-sm" dir="auto">
                                    {iteration.objective}
                                </p>

                                {iteration.hypothesis && (
                                    <p
                                        className="text-sm text-muted-foreground"
                                        dir="auto"
                                    >
                                        <span className="font-medium">
                                            {t('We expected:')}{' '}
                                        </span>
                                        {iteration.hypothesis}
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <DesignChangeList
                            changes={changes}
                            workspace={workspace.slug}
                            game={game.slug}
                            iteration={iteration.id}
                            options={options}
                            canRecordWork={permissions.canRecordWork}
                        />

                        <ExperimentList
                            experiments={experiments}
                            workspace={workspace.slug}
                            game={game.slug}
                            iteration={iteration.id}
                            canRecordWork={permissions.canRecordWork}
                        />

                        <RelatedPlaytests
                            playtests={playtests}
                            available={availablePlaytests}
                            workspace={workspace.slug}
                            game={game.slug}
                            iteration={iteration.id}
                            canAttach={permissions.canAttachPlaytest}
                        />

                        <DecisionList
                            decisions={decisions}
                            evidence={evidence}
                            playtests={playtests}
                            workspace={workspace.slug}
                            game={game.slug}
                            iteration={iteration.id}
                            options={options}
                            canRecordWork={permissions.canRecordWork}
                        />
                    </div>

                    <div className="space-y-6">
                        <IterationSummary summary={summary} />

                        <Card data-test="timeline-panel">
                            <CardHeader>
                                <CardTitle className="text-base">
                                    {t('Timeline')}
                                </CardTitle>
                            </CardHeader>

                            <CardContent>
                                <IterationTimeline
                                    timeline={timeline}
                                    workspace={workspace.slug}
                                    game={game.slug}
                                />
                            </CardContent>
                        </Card>

                        {permissions.canCreateGameVersion && (
                            <Card data-test="next-game-version">
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        {t('Has the design moved on?')}
                                    </CardTitle>
                                </CardHeader>

                                <CardContent className="space-y-3">
                                    <p className="text-sm text-muted-foreground">
                                        {t(
                                            'If what this iteration concluded amounts to a new state of the design, cut the next game version. Nothing does this automatically — most cycles do not need one.',
                                        )}
                                    </p>

                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            createNextGameVersion(
                                                {
                                                    workspace: workspace.slug,
                                                    game: game.slug,
                                                },
                                                iteration.id,
                                                emptyNextGameVersionInput,
                                                { preserveScroll: false },
                                            )
                                        }
                                        data-test="create-game-version-button"
                                    >
                                        <GitBranchPlus className="size-4" />
                                        {t('Create next game version')}
                                    </Button>

                                    {prototypeVersions.length === 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            {t(
                                                'You can associate a prototype version with it afterwards.',
                                            )}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
