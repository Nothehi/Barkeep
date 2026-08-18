import { Head, Link } from '@inertiajs/react';
import { GitBranch } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Workspace } from '@/features/workspaces';
import { useFormatters, useTranslation } from '@/lib/i18n';
import versions from '@/routes/games/versions';
import DesignPhasePicker from '../components/design-phase-picker';
import EditGameDialog from '../components/edit-game-dialog';
import GameHeader from '../components/game-header';
import GameProgress from '../components/game-progress';
import type { Game, GameDashboard, GameOptions } from '../types/game';

type GamePageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    dashboard: GameDashboard;
    options: GameOptions;
};

/**
 * A game's overview.
 *
 * Everything on this screen is something the platform can actually answer
 * today: what the game is, where it is, and how many times it has been
 * iterated. There are no playtest counts, no feedback summaries and no
 * balance readings, because the contexts that would produce them do not
 * exist — and a dashboard of invented numbers is worse than a small honest
 * one.
 */
export default function GamePage({
    workspace: { data: workspace },
    game: { data: game },
    dashboard,
    options,
}: GamePageProps) {
    const { t } = useTranslation();
    const { formatDate, formatNumber } = useFormatters();
    const latest = dashboard.latest_version?.data ?? null;

    return (
        <>
            <Head
                title={t(':game · :workspace', {
                    game: game.name,
                    workspace: workspace.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <div className="flex items-start justify-between gap-3">
                                <CardTitle>{t('About')}</CardTitle>
                                <EditGameDialog
                                    game={game}
                                    workspace={workspace.slug}
                                />
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            <p
                                className="text-sm text-muted-foreground"
                                dir="auto"
                            >
                                {game.description ?? t('No description yet.')}
                            </p>

                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        {t('Address')}
                                    </dt>
                                    <dd className="font-medium" dir="ltr">
                                        /{game.slug}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-muted-foreground">
                                        {t('Started')}
                                    </dt>
                                    <dd className="font-medium">
                                        {game.created_at
                                            ? formatDate(game.created_at)
                                            : '—'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-muted-foreground">
                                        {t('Last updated')}
                                    </dt>
                                    <dd className="font-medium">
                                        {game.updated_at
                                            ? formatDate(game.updated_at)
                                            : '—'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-muted-foreground">
                                        {t('Versions')}
                                    </dt>
                                    <dd className="font-medium">
                                        {formatNumber(dashboard.versions_count)}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Design phase')}</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-4">
                                <GameProgress
                                    position={game.design_phase_position}
                                    total={game.design_phase_count}
                                    label={game.design_phase_label}
                                />

                                <DesignPhasePicker
                                    game={game}
                                    workspace={workspace.slug}
                                    options={options.design_phases}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Current version')}</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-4">
                                {latest ? (
                                    <div className="space-y-1">
                                        <p className="font-medium">
                                            {latest.label}
                                            {latest.name && ` · ${latest.name}`}
                                        </p>

                                        {latest.description && (
                                            <p
                                                className="line-clamp-3 text-sm text-muted-foreground"
                                                dir="auto"
                                            >
                                                {latest.description}
                                            </p>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        {t('No versions cut yet.')}
                                    </p>
                                )}

                                <Button variant="outline" asChild>
                                    <Link
                                        href={versions.index({
                                            workspace: workspace.slug,
                                            game: game.slug,
                                        })}
                                    >
                                        <GitBranch className="size-4" />
                                        {t('All versions')}
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
