import { CheckCircle2, PauseCircle, PlayCircle } from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import type { GameFrameworkStatus } from '../types/framework';

type AdoptionStatusBadgeProps = {
    status: GameFrameworkStatus;
    label?: string;
};

const PRESENTATION: Record<
    GameFrameworkStatus,
    {
        icon: ComponentType<{ className?: string }>;
        variant: 'default' | 'secondary' | 'outline';
    }
> = {
    active: { icon: PlayCircle, variant: 'default' },
    paused: { icon: PauseCircle, variant: 'outline' },
    completed: { icon: CheckCircle2, variant: 'secondary' },
};

const FALLBACK_LABEL: Record<GameFrameworkStatus, string> = {
    active: 'Active',
    paused: 'Paused',
    completed: 'Completed',
};

/**
 * Where a game's relationship with its methodology stands.
 *
 * Paused is drawn as an outline rather than as a warning tone on purpose. A
 * studio stepping away from a process for a month is a normal thing designers
 * do, and the badge exists so that stopping can be honest rather than so it
 * can be scolded.
 */
export default function AdoptionStatusBadge({
    status,
    label,
}: AdoptionStatusBadgeProps) {
    const { icon: Icon, variant } = PRESENTATION[status];

    return (
        <Badge variant={variant} data-test={`adoption-status-${status}`}>
            <Icon className="size-3" />
            {label ?? FALLBACK_LABEL[status]}
        </Badge>
    );
}
