import { Link } from '@inertiajs/react';
import { CalendarDays, ClipboardList, Layers } from 'lucide-react';
import { PlaytestStatusBadge } from '@/features/playtesting';
import { useFormatters, useTranslation } from '@/lib/i18n';
import playtests from '@/routes/playtests';
import type { WorkspacePlaytest } from '../types/dashboard';

type RecentPlaytestsProps = {
    playtests: WorkspacePlaytest[];
    workspace: string;
};

/**
 * What the studio has been trying to find out lately, across every game.
 *
 * The game's name is on every row and is not decoration: a playtest address is
 * nested under its game, so without it there is nowhere for the row to lead —
 * and "does the opening still stall?" identifies an investigation inside one
 * project and nothing at all across four.
 *
 * A playtest whose game is missing is left out rather than drawn half-linked.
 * Nothing in the domain currently produces one, and a row that cannot be
 * opened is worse than a shorter list.
 */
export default function RecentPlaytests({
    playtests: recent,
    workspace,
}: RecentPlaytestsProps) {
    const { t, choice } = useTranslation();
    const { formatDate, formatNumber } = useFormatters();

    const rows = recent.flatMap((playtest) =>
        playtest.game.slug === null
            ? []
            : [{ playtest, game: playtest.game.slug }],
    );

    if (rows.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-6 py-10 text-center">
                <ClipboardList className="mx-auto size-8 text-muted-foreground" />

                <p className="mt-3 font-medium">{t('Nothing tested yet')}</p>

                <p className="mt-1 text-sm text-muted-foreground">
                    {t(
                        'A playtest names a question about one version of a game and gathers evidence towards it. The first one is planned from the game it is about.',
                    )}
                </p>
            </div>
        );
    }

    return (
        <ul className="divide-y">
            {rows.map(({ playtest, game }) => (
                <li
                    key={playtest.id}
                    className="relative py-3 first:pt-0 last:pb-0"
                >
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <Link
                            href={playtests.show.url({
                                workspace,
                                game,
                                playtest: playtest.id,
                            })}
                            className="min-w-0 truncate font-medium after:absolute after:inset-0 hover:underline"
                            dir="auto"
                        >
                            {playtest.title}
                        </Link>

                        <PlaytestStatusBadge
                            status={playtest.status}
                            label={playtest.status_label}
                        />
                    </div>

                    <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        <span className="truncate" dir="auto">
                            {playtest.game.name}
                        </span>

                        {playtest.version_label && (
                            <span>{playtest.version_label}</span>
                        )}

                        <span className="inline-flex items-center gap-1">
                            <Layers className="size-3" />
                            {choice(
                                ':count session|:count sessions',
                                playtest.sessions_count ?? 0,
                                {
                                    count: formatNumber(
                                        playtest.sessions_count ?? 0,
                                    ),
                                },
                            )}
                        </span>

                        {playtest.planned_at && (
                            <span className="inline-flex items-center gap-1">
                                <CalendarDays className="size-3" />
                                {formatDate(playtest.planned_at)}
                            </span>
                        )}
                    </p>
                </li>
            ))}
        </ul>
    );
}
