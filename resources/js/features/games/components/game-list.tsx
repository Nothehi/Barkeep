import { Gamepad2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { GameSummary } from '../types/game';
import GameCard from './game-card';

type GameListProps = {
    games: GameSummary[];
    workspace: string;
    isFiltered: boolean;
    onClearFilters: () => void;
};

/**
 * The games in a workspace, or an explanation of why there are none.
 *
 * The two empty states are different questions and get different answers: a
 * workspace with no games at all needs an invitation to start one, whereas a
 * filtered list that came back empty needs a way out of the filter. Showing
 * "create your first game" to somebody who has forty games and a narrow
 * filter would be nonsense.
 */
export default function GameList({
    games,
    workspace,
    isFiltered,
    onClearFilters,
}: GameListProps) {
    if (games.length === 0 && isFiltered) {
        return (
            <div className="rounded-lg border border-dashed px-6 py-16 text-center">
                <p className="font-medium">No games match those filters</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    Try a different search, or widen the status and phase.
                </p>

                <Button
                    variant="outline"
                    className="mt-6"
                    onClick={onClearFilters}
                    data-test="clear-game-filters-button"
                >
                    Clear filters
                </Button>
            </div>
        );
    }

    if (games.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-6 py-16 text-center">
                <Gamepad2 className="mx-auto size-8 text-muted-foreground" />
                <p className="mt-3 font-medium">No games yet</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    Start one to give an idea somewhere to live. It begins as a
                    draft, and nobody sees it outside this workspace.
                </p>
            </div>
        );
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {games.map((game) => (
                <GameCard key={game.id} game={game} workspace={workspace} />
            ))}
        </div>
    );
}
