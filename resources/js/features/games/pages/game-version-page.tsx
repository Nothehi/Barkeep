import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Workspace } from '@/features/workspaces';
import { useFormatters, useTranslation } from '@/lib/i18n';
import versionRoutes from '@/routes/games/versions';
import GameHeader from '../components/game-header';
import type { Game, GameVersion } from '../types/game';

type GameVersionPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    is_current: boolean;
};

/**
 * One iteration of a game.
 *
 * Small, because a version is currently small: a number, who cut it, when,
 * and what changed. It is the anchor future work hangs off — a playtest
 * session will point at a version rather than at a game — so it has a URL of
 * its own from the start.
 */
export default function GameVersionPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    is_current: isCurrent,
}: GameVersionPageProps) {
    const { t } = useTranslation();
    const { formatDate } = useFormatters();

    return (
        <>
            <Head
                title={t(':version · :game', {
                    version: version.label,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <Button variant="ghost" size="sm" asChild>
                    <Link
                        href={versionRoutes.index({
                            workspace: workspace.slug,
                            game: game.slug,
                        })}
                    >
                        <ArrowLeft className="size-4 rtl:rotate-180" />
                        {t('All versions')}
                    </Link>
                </Button>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <div className="flex flex-wrap items-center gap-3">
                            <CardTitle>{version.label}</CardTitle>

                            {version.name && (
                                <span
                                    className="text-sm text-muted-foreground"
                                    dir="auto"
                                >
                                    {version.name}
                                </span>
                            )}

                            {isCurrent && (
                                <Badge variant="secondary" className="ms-auto">
                                    {t('Current')}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <p
                            className="text-sm whitespace-pre-line text-muted-foreground"
                            dir="auto"
                        >
                            {version.description ??
                                t('No notes were left on this version.')}
                        </p>

                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">
                                    {t('Cut by')}
                                </dt>
                                <dd className="font-medium" dir="auto">
                                    {version.creator?.name ?? t('Someone')}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-muted-foreground">
                                    {t('Cut on')}
                                </dt>
                                <dd className="font-medium">
                                    {version.created_at
                                        ? formatDate(version.created_at)
                                        : '—'}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
