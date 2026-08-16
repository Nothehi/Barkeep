import { FlaskConical } from 'lucide-react';
import type { PlaytestSummary } from '../types/playtest';
import PlaytestCard from './playtest-card';

type PlaytestListProps = {
    playtests: PlaytestSummary[];
    workspace: string;
    game: string;
    isFiltered: boolean;
};

/**
 * The playtests of a game.
 *
 * The two empty states say different things and that is worth the extra
 * branch: "no playtests yet" is an invitation, and "nothing matched" is a
 * filter problem. Showing the first when somebody has typed a search would
 * tell them their game has no playtests, which is false.
 */
export default function PlaytestList({
    playtests,
    workspace,
    game,
    isFiltered,
}: PlaytestListProps) {
    if (playtests.length === 0) {
        return (
            <div
                className="flex flex-col items-center gap-3 rounded-lg border border-dashed py-14 text-center"
                data-test="playtests-empty"
            >
                <FlaskConical className="size-8 text-muted-foreground" />

                <div className="space-y-1">
                    <p className="font-medium">
                        {isFiltered
                            ? 'No playtests match those filters'
                            : 'No playtests yet'}
                    </p>

                    <p className="max-w-md text-sm text-muted-foreground">
                        {isFiltered
                            ? 'Try a different search or clear the status filter.'
                            : 'A playtest is a question about a version of this game — what you want to find out, and who you are going to find it out with.'}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div
            className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            data-test="playtest-list"
        >
            {playtests.map((playtest) => (
                <PlaytestCard
                    key={playtest.id}
                    playtest={playtest}
                    workspace={workspace}
                    game={game}
                />
            ))}
        </div>
    );
}
