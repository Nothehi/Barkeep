import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { changeGameStatus } from '../api';
import { useGamePermissions } from '../hooks/use-game-permissions';
import type { Game, GameStatus } from '../types/game';

type ChangeStatusDialogProps = {
    game: Game;
    workspace: string;
};

/**
 * The lifecycle moves a game can currently make.
 *
 * Deliberately not a dropdown of every status. A game does not go from draft
 * straight to completed, and offering a select that lets somebody try would
 * mean the interface proposing things the server will refuse.
 *
 * Instead the server sends the moves this game can make, already worded —
 * "Start designing" from a draft, "Resume" from on hold — and this renders
 * one button per move. An archived game gets none, and so does a reader.
 */
export default function ChangeStatusDialog({
    game,
    workspace,
}: ChangeStatusDialogProps) {
    const permissions = useGamePermissions(game);
    const [pending, setPending] = useState<GameStatus | null>(null);

    if (
        !permissions.canChangeStatus ||
        game.available_transitions.length === 0
    ) {
        return null;
    }

    const move = (status: GameStatus) => {
        setPending(status);

        changeGameStatus(workspace, game.slug, status, {
            onFinish: () => setPending(null),
        });
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            {game.available_transitions.map((transition, index) => (
                <Button
                    key={transition.status}
                    size="sm"
                    variant={index === 0 ? 'default' : 'outline'}
                    onClick={() => move(transition.status)}
                    disabled={pending !== null}
                    data-test={`game-transition-${transition.status}`}
                >
                    {pending === transition.status && <Spinner />}
                    {transition.label}
                </Button>
            ))}
        </div>
    );
}
