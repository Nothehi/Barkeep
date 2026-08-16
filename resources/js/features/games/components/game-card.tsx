import { Link } from '@inertiajs/react';
import { GitBranch } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import games from '@/routes/games';
import type { GameSummary } from '../types/game';
import DesignPhaseBadge from './design-phase-badge';
import GameStatusBadge from './game-status-badge';

type GameCardProps = {
    game: GameSummary;
    workspace: string;
};

/**
 * One game in the games list.
 *
 * Shows both badges, because status and phase answer different questions and
 * somebody scanning a studio's projects wants both: what is being worked on,
 * and how far each one has got.
 */
export default function GameCard({ game, workspace }: GameCardProps) {
    return (
        <Card className="relative transition-colors hover:border-ring">
            <CardHeader>
                <CardTitle className="min-w-0">
                    <Link
                        href={games.show({ workspace, game: game.slug })}
                        className="block truncate after:absolute after:inset-0"
                    >
                        {game.name}
                    </Link>
                </CardTitle>

                <p className="truncate text-sm text-muted-foreground">
                    /{game.slug}
                </p>
            </CardHeader>

            <CardContent className="space-y-3">
                {game.description && (
                    <p className="line-clamp-2 text-sm text-muted-foreground">
                        {game.description}
                    </p>
                )}

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

                {game.versions_count !== undefined &&
                    game.versions_count > 0 && (
                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <GitBranch className="size-4" />
                            {game.versions_count}
                            {game.versions_count === 1
                                ? ' version'
                                : ' versions'}
                        </p>
                    )}
            </CardContent>
        </Card>
    );
}
