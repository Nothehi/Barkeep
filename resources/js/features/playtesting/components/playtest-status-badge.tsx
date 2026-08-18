import {
    CalendarClock,
    CheckCircle2,
    CircleSlash,
    FlaskConical,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';
import type { PlaytestStatus } from '../types/playtest';

type PlaytestStatusBadgeProps = {
    status: PlaytestStatus;
    label?: string;
};

/**
 * The icon and tone each stage of an investigation is drawn in.
 *
 * Only the presentation lives here. The labels come from the server, so this
 * map never has to be kept in step with the wording.
 */
const PRESENTATION: Record<
    PlaytestStatus,
    {
        icon: ComponentType<{ className?: string }>;
        variant: 'default' | 'secondary' | 'outline';
    }
> = {
    planned: { icon: CalendarClock, variant: 'outline' },
    in_progress: { icon: FlaskConical, variant: 'default' },
    completed: { icon: CheckCircle2, variant: 'secondary' },
    cancelled: { icon: CircleSlash, variant: 'secondary' },
};

const FALLBACK_LABEL: Record<PlaytestStatus, string> = {
    planned: 'Planned',
    in_progress: 'In progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

/**
 * Where an investigation is in its own life.
 *
 * Distinct from {@link SessionStatusBadge}, which is about one sitting. Both
 * appear on the same screen and they mean different things: a playtest with
 * four completed sessions is still in progress until the designer says
 * otherwise.
 */
export default function PlaytestStatusBadge({
    status,
    label,
}: PlaytestStatusBadgeProps) {
    const { t } = useTranslation();
    const { icon: Icon, variant } = PRESENTATION[status];

    return (
        <Badge variant={variant} data-test={`playtest-status-${status}`}>
            <Icon className="size-3" />
            {label ?? t(FALLBACK_LABEL[status])}
        </Badge>
    );
}
