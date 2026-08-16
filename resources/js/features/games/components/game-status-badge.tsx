import {
    Archive,
    CheckCircle2,
    Circle,
    PauseCircle,
    PlayCircle,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import type { GameStatus } from '../types/game';

type GameStatusBadgeProps = {
    status: GameStatus;
    label?: string;
};

/**
 * The icon and tone each lifecycle state is drawn in.
 *
 * Only the presentation lives here. The labels come from the server, so this
 * map never has to be kept in step with the wording.
 */
const PRESENTATION: Record<
    GameStatus,
    {
        icon: ComponentType<{ className?: string }>;
        variant: 'default' | 'secondary' | 'outline';
    }
> = {
    draft: { icon: Circle, variant: 'outline' },
    active: { icon: PlayCircle, variant: 'default' },
    on_hold: { icon: PauseCircle, variant: 'secondary' },
    completed: { icon: CheckCircle2, variant: 'secondary' },
    archived: { icon: Archive, variant: 'secondary' },
};

const FALLBACK_LABEL: Record<GameStatus, string> = {
    draft: 'Draft',
    active: 'Active',
    on_hold: 'On hold',
    completed: 'Completed',
    archived: 'Archived',
};

/**
 * Where a game project is in its own life.
 *
 * Distinct from {@link DesignPhaseBadge}, which is about the design. Both
 * appear together on a game, and they mean different things: a game can be on
 * hold in the middle of playtesting.
 */
export default function GameStatusBadge({
    status,
    label,
}: GameStatusBadgeProps) {
    const { icon: Icon, variant } = PRESENTATION[status];

    return (
        <Badge variant={variant} data-test={`game-status-${status}`}>
            <Icon className="size-3" />
            {label ?? FALLBACK_LABEL[status]}
        </Badge>
    );
}
