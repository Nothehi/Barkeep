import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { Game } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { moveAdoption } from '../api';
import AdoptFrameworkPanel from '../components/adopt-framework-panel';
import AdoptionStatusBadge from '../components/adoption-status-badge';
import PhaseNav from '../components/phase-nav';
import ProgressBar from '../components/progress-bar';
import TransitionButtons from '../components/transition-buttons';
import type {
    DesignPhase,
    Framework,
    FrameworkProgress,
    GameFramework,
    GameFrameworkStatus,
    RatingOption,
} from '../types/framework';

type GameFrameworkPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    adoption: { data: GameFramework } | null;
    progress: { data: FrameworkProgress } | null;
    phases: { data: DesignPhase[] };
    available: { data: Framework[] };
    options: { ratings: RatingOption[] };
    can: { assign: boolean };
};

/**
 * A game's framework.
 *
 * One screen answering two questions depending on whether the game follows a
 * methodology yet. Before: here are the published frameworks, pick one.
 * After: here is where you are, phase by phase.
 *
 * The catalogue is only sent when there is a choice to make, so a game already
 * following a methodology cannot be offered a switch — changing edition is
 * migration, and the module does not implement it.
 */
export default function GameFrameworkPage({
    workspace: { data: workspace },
    game: { data: game },
    adoption,
    progress,
    phases: { data: phases },
    available: { data: available },
    can,
}: GameFrameworkPageProps) {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();
    const following = adoption?.data ?? null;
    const stats = progress?.data ?? null;

    return (
        <>
            <Head title={t('Framework · :game', { game: game.name })} />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                {following === null ? (
                    <>
                        <Heading
                            variant="small"
                            title={t('Design framework')}
                            description={t(
                                'A methodology this game can follow, phase by phase',
                            )}
                        />

                        <AdoptFrameworkPanel
                            workspace={workspace.slug}
                            game={game.slug}
                            frameworks={available}
                            canAssign={can.assign}
                        />
                    </>
                ) : (
                    <>
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="space-y-2">
                                <Heading
                                    variant="small"
                                    title={
                                        following.framework?.name ??
                                        t('Design framework')
                                    }
                                    description={
                                        following.version
                                            ? t('Following :edition', {
                                                  edition:
                                                      following.version.label,
                                              })
                                            : undefined
                                    }
                                />

                                <AdoptionStatusBadge
                                    status={following.status}
                                    label={following.status_label}
                                />
                            </div>

                            <TransitionButtons
                                transitions={following.available_transitions}
                                testPrefix="adoption-transition"
                                onMove={(status, done) =>
                                    moveAdoption(
                                        workspace.slug,
                                        game.slug,
                                        status as GameFrameworkStatus,
                                        { onFinish: done },
                                    )
                                }
                            />
                        </div>

                        {stats && (
                            <Card data-test="framework-progress">
                                <CardHeader className="gap-1">
                                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                                        <span className="font-medium">
                                            {t('Progress')}
                                        </span>

                                        <span className="text-2xl font-semibold tabular-nums">
                                            {t(':percent%', {
                                                percent: formatNumber(
                                                    stats.percentage,
                                                ),
                                            })}
                                        </span>
                                    </div>

                                    {/*
                                     * Said out loud rather than left to be
                                     * inferred from a bar that does not move.
                                     * A designer who answers six prompts and
                                     * sees no change deserves to know that was
                                     * the intent.
                                     */}
                                    <span className="text-sm text-muted-foreground">
                                        {t(
                                            'Counted from assessed criteria, completed practices and required checklist items. Written answers are reported but not counted.',
                                        )}
                                    </span>
                                </CardHeader>

                                <CardContent className="grid gap-4 sm:grid-cols-2">
                                    <ProgressBar
                                        label={t('Criteria assessed')}
                                        ratio={stats.criteria}
                                    />
                                    <ProgressBar
                                        label={t('Practices completed')}
                                        ratio={stats.practices}
                                    />
                                    <ProgressBar
                                        label={t('Checklist items')}
                                        ratio={stats.checklist_items}
                                    />
                                    <ProgressBar
                                        label={t('Questions answered')}
                                        ratio={stats.prompts}
                                        uncounted
                                    />
                                </CardContent>
                            </Card>
                        )}

                        <div className="space-y-3">
                            <h2 className="text-sm font-medium">
                                {t('Phases')}
                            </h2>

                            <PhaseNav
                                workspace={workspace.slug}
                                game={game.slug}
                                phases={phases}
                                progress={stats?.phase_progress ?? []}
                            />
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
