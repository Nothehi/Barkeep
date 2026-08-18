import { CalendarClock, CircleSlash, Radio, Square } from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';
import type { SessionStatus } from '../types/playtest';

type SessionStatusBadgeProps = {
    status: SessionStatus;
    label?: string;
};

/**
 * The icon and tone each stage of a sitting is drawn in.
 *
 * A running session gets the loudest treatment on the screen. It is the one
 * state somebody is looking at while standing at a table, and finding it has
 * to take no thought at all.
 */
const PRESENTATION: Record<
    SessionStatus,
    {
        icon: ComponentType<{ className?: string }>;
        variant: 'default' | 'secondary' | 'outline';
    }
> = {
    planned: { icon: CalendarClock, variant: 'outline' },
    in_progress: { icon: Radio, variant: 'default' },
    completed: { icon: Square, variant: 'secondary' },
    cancelled: { icon: CircleSlash, variant: 'secondary' },
};

const FALLBACK_LABEL: Record<SessionStatus, string> = {
    planned: 'Planned',
    in_progress: 'In progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

export default function SessionStatusBadge({
    status,
    label,
}: SessionStatusBadgeProps) {
    const { t } = useTranslation();
    const { icon: Icon, variant } = PRESENTATION[status];

    return (
        <Badge variant={variant} data-test={`session-status-${status}`}>
            <Icon className="size-3" />
            {label ?? t(FALLBACK_LABEL[status])}
        </Badge>
    );
}
