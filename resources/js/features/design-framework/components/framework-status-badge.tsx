import { Archive, CircleCheck, PencilLine } from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import type { FrameworkStatus } from '../types/framework';

type FrameworkStatusBadgeProps = {
    status: FrameworkStatus;
    label?: string;
};

/**
 * The icon and tone each stage of a methodology's life is drawn in.
 *
 * Only the presentation lives here. The labels come from the server, so this
 * map never has to be kept in step with the wording.
 */
const PRESENTATION: Record<
    FrameworkStatus,
    {
        icon: ComponentType<{ className?: string }>;
        variant: 'default' | 'secondary' | 'outline';
    }
> = {
    draft: { icon: PencilLine, variant: 'outline' },
    published: { icon: CircleCheck, variant: 'default' },
    archived: { icon: Archive, variant: 'secondary' },
};

const FALLBACK_LABEL: Record<FrameworkStatus, string> = {
    draft: 'Draft',
    published: 'Published',
    archived: 'Archived',
};

/**
 * Where a methodology, or one of its editions, stands.
 *
 * Distinct from {@link AdoptionStatusBadge}, which is about one game's
 * relationship with a methodology. Both appear on a game's framework screen
 * and they mean different things: a published framework and a paused adoption
 * are perfectly consistent.
 */
export default function FrameworkStatusBadge({
    status,
    label,
}: FrameworkStatusBadgeProps) {
    const { icon: Icon, variant } = PRESENTATION[status];

    return (
        <Badge variant={variant} data-test={`framework-status-${status}`}>
            <Icon className="size-3" />
            {label ?? FALLBACK_LABEL[status]}
        </Badge>
    );
}
