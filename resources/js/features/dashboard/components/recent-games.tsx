import { Link } from '@inertiajs/react';
import { GitBranch, Gamepad2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DesignPhaseBadge, GameStatusBadge } from '@/features/games';
import type { GameSummary } from '@/features/games';
import { useFormatters, useTranslation } from '@/lib/i18n';
import games from '@/routes/games';

type RecentGamesProps = {
    games: GameSummary[];
    workspace: string;
    canCreate: boolean;
};

/**
 * The games somebody here touched most recently.
 *
 * Rows rather than the cards the games screen uses: this is a summary that
 * has to sit beside three other panels, and a grid of cards inside a card
 * reads as a second games screen rather than as a way back into work. What
 * survives the compression is what identifies a project — its name, whether it
 * is being worked on, and how far it has got.
 *
 * The empty state offers a way to start rather than explaining the absence,
 * because a workspace with no games has exactly one useful next move.
 */
export default function RecentGames({
    games: recent,
    workspace,
    canCreate,
}: RecentGamesProps) {
    const { t, choice } = useTranslation();
    const { formatDate, formatNumber } = useFormatters();

    if (recent.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-6 py-10 text-center">
                <Gamepad2 className="mx-auto size-8 text-muted-foreground" />

                <p className="mt-3 font-medium">{t('No games yet')}</p>

                <p className="mt-1 text-sm text-muted-foreground">
                    {t(
                        'Start one to give an idea somewhere to live. It begins as a draft, and nobody sees it outside this workspace.',
                    )}
                </p>

                {canCreate && (
                    <Button variant="outline" className="mt-6" asChild>
                        <Link href={games.index(workspace)}>
                            <Gamepad2 className="size-4" />
                            {t('Start a game')}
                        </Link>
                    </Button>
                )}
            </div>
        );
    }

    return (
        <ul className="divide-y">
            {recent.map((game) => (
                <li
                    key={game.id}
                    className="relative py-3 first:pt-0 last:pb-0"
                >
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <Link
                            href={games.show({ workspace, game: game.slug })}
                            className="min-w-0 truncate font-medium after:absolute after:inset-0 hover:underline"
                            dir="auto"
                        >
                            {game.name}
                        </Link>

                        <div className="flex flex-wrap items-center gap-2">
                            <GameStatusBadge
                                status={game.status}
                                label={game.status_label}
                            />

                            <DesignPhaseBadge
                                phase={game.design_phase}
                                label={game.design_phase_label}
                            />
                        </div>
                    </div>

                    <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {game.versions_count !== undefined &&
                            game.versions_count > 0 && (
                                <span className="inline-flex items-center gap-1">
                                    <GitBranch className="size-3" />
                                    {choice(
                                        ':count version|:count versions',
                                        game.versions_count,
                                        {
                                            count: formatNumber(
                                                game.versions_count,
                                            ),
                                        },
                                    )}
                                </span>
                            )}

                        {game.updated_at && (
                            <span>
                                {t('Last worked on :date', {
                                    date: formatDate(game.updated_at),
                                })}
                            </span>
                        )}
                    </p>
                </li>
            ))}
        </ul>
    );
}
