import { Head, Link } from '@inertiajs/react';
import {
    Blocks,
    ClipboardList,
    Gamepad2,
    Layers,
    Library,
    RefreshCw,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Workspace } from '@/features/workspaces';
import { WorkspaceHeader } from '@/features/workspaces';
import { useFormatters, useTranslation } from '@/lib/i18n';
import frameworks from '@/routes/frameworks';
import games from '@/routes/games';
import mechanics from '@/routes/mechanics';
import members from '@/routes/workspaces/members';
import PhaseDistribution from '../components/phase-distribution';
import RecentGames from '../components/recent-games';
import RecentPlaytests from '../components/recent-playtests';
import StatTile from '../components/stat-tile';
import type {
    DashboardGames,
    DashboardIteration,
    DashboardPlaytesting,
} from '../types/dashboard';

export type DashboardPageProps = {
    workspace: { data: Workspace };
    games: DashboardGames;
    playtesting: DashboardPlaytesting;
    iteration: DashboardIteration;
    can: { createGame: boolean };
};

/**
 * The app's home, and so the screen every sign in lands on.
 *
 * It answers one question — "what is going on in this studio?" — and every
 * figure on it is something the platform can actually count today: games and
 * the versions cut from them, playtests and the sittings they were made of,
 * prototypes and the cycles run against them. Nothing here is a projection, a
 * score or a trend, because none of those have a real source yet, and a
 * dashboard of invented numbers is worse than a small honest one.
 *
 * The layout follows the reading order rather than the module boundaries: a
 * scoreboard of four figures, then the two things worth clicking into, then
 * the shape of the work and the ways out of the workspace. Somebody arriving
 * here should be able to get back to what they were doing in one move.
 *
 * There is deliberately nothing about rule sets or balance profiles. Both
 * belong to a version of a game rather than to a studio, so a workspace-wide
 * count of them would be a number with no question behind it.
 */
export default function DashboardPage({
    workspace: { data: workspace },
    games: gameActivity,
    playtesting,
    iteration,
    can,
}: DashboardPageProps) {
    const { t, choice } = useTranslation();
    const { formatNumber } = useFormatters();

    /**
     * Only the statuses something is actually sitting in. Unlike the phases
     * below, the lifecycle is not an arc to be read end to end — a pill saying
     * "0 archived" is noise rather than information.
     */
    const occupiedStatuses = gameActivity.by_status.filter(
        (status) => status.count > 0,
    );

    return (
        <>
            <Head
                title={t('Dashboard · :workspace', {
                    workspace: workspace.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <WorkspaceHeader workspace={workspace} />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatTile
                        label={t('Games')}
                        value={gameActivity.total}
                        icon={Gamepad2}
                        href={games.index.url(workspace.slug)}
                        hint={choice(
                            ':count version cut|:count versions cut',
                            gameActivity.versions_count,
                            {
                                count: formatNumber(
                                    gameActivity.versions_count,
                                ),
                            },
                        )}
                    />

                    <StatTile
                        label={t('Playtests')}
                        value={playtesting.total}
                        icon={ClipboardList}
                        hint={choice(
                            ':count session held|:count sessions held',
                            playtesting.sessions_count,
                            { count: formatNumber(playtesting.sessions_count) },
                        )}
                    />

                    <StatTile
                        label={t('Prototypes')}
                        value={iteration.prototypes_count}
                        icon={Layers}
                        hint={choice(
                            ':count iteration|:count iterations',
                            iteration.iterations_count,
                            { count: formatNumber(iteration.iterations_count) },
                        )}
                    />

                    <StatTile
                        label={t('Open cycles')}
                        value={iteration.open_iterations_count}
                        icon={RefreshCw}
                        hint={t('Planned or under way')}
                    />
                </div>

                <div className="grid items-start gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <CardTitle>
                                        {t('Recently worked on')}
                                    </CardTitle>

                                    {gameActivity.total > 0 && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link
                                                href={games.index(
                                                    workspace.slug,
                                                )}
                                            >
                                                {t('All games')}
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>

                            <CardContent>
                                <RecentGames
                                    games={gameActivity.recent.data}
                                    workspace={workspace.slug}
                                    canCreate={can.createGame}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Latest playtests')}</CardTitle>
                            </CardHeader>

                            <CardContent>
                                <RecentPlaytests
                                    playtests={playtesting.recent.data}
                                    workspace={workspace.slug}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        {gameActivity.total > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        {t('Where the work sits')}
                                    </CardTitle>
                                </CardHeader>

                                <CardContent className="space-y-5">
                                    <div className="flex flex-wrap gap-2">
                                        {occupiedStatuses.map((status) => (
                                            <Badge
                                                key={status.value}
                                                variant="secondary"
                                            >
                                                {status.label}
                                                <span className="tabular-nums">
                                                    {formatNumber(status.count)}
                                                </span>
                                            </Badge>
                                        ))}
                                    </div>

                                    <PhaseDistribution
                                        phases={gameActivity.by_design_phase}
                                    />
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Team')}</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    {choice(
                                        ':count person in this workspace.|:count people in this workspace.',
                                        workspace.members_count ?? 0,
                                    )}
                                </p>

                                <Button variant="outline" asChild>
                                    <Link href={members.index(workspace.slug)}>
                                        <Users className="size-4" />
                                        {t('View members')}
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Shared reference')}</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'The vocabulary and the methodologies belong to the platform rather than to this workspace, so every game draws on the same ones.',
                                    )}
                                </p>

                                <div className="flex flex-wrap gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={mechanics.index()}>
                                            <Blocks className="size-4" />
                                            {t('Mechanics')}
                                        </Link>
                                    </Button>

                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={frameworks.index()}>
                                            <Library className="size-4" />
                                            {t('Frameworks')}
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
