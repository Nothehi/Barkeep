import {
    Ban,
    CalendarClock,
    CheckCircle2,
    CircleDashed,
    CircleHelp,
    CircleSlash,
    FlaskConical,
    Hammer,
    PauseCircle,
    PencilRuler,
    ThumbsDown,
    ThumbsUp,
    TriangleAlert,
    XCircle,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';
import type {
    DecisionStatus,
    ExperimentStatus,
    IterationOutcome,
    IterationStatus,
    PrototypeStatus,
} from '../types/prototype-iteration';

/**
 * The icon and tone each state in this module is drawn in.
 *
 * Only the presentation lives here. Every label comes from the server, worded by the enum that defines the
 * state, so these maps never have to be kept in step with the wording — a renamed status changes in one place
 * and the badge follows.
 *
 * The fallback labels exist for the same reason they do elsewhere in the application: a badge rendered from a
 * card resource that carries a status and no label should still read as something.
 */

type Presentation = {
    icon: ComponentType<{ className?: string }>;
    variant: 'default' | 'secondary' | 'outline' | 'destructive';
};

const PROTOTYPE: Record<PrototypeStatus, Presentation> = {
    draft: { icon: PencilRuler, variant: 'outline' },
    active: { icon: Hammer, variant: 'default' },
    archived: { icon: CircleSlash, variant: 'secondary' },
};

const PROTOTYPE_FALLBACK: Record<PrototypeStatus, string> = {
    draft: 'Draft',
    active: 'Active',
    archived: 'Archived',
};

export function PrototypeStatusBadge({
    status,
    label,
}: {
    status: PrototypeStatus;
    label?: string;
}) {
    const { t } = useTranslation();
    const { icon: Icon, variant } = PROTOTYPE[status];

    return (
        <Badge variant={variant} data-test={`prototype-status-${status}`}>
            <Icon className="size-3" />
            {label ?? t(PROTOTYPE_FALLBACK[status])}
        </Badge>
    );
}

const ITERATION: Record<IterationStatus, Presentation> = {
    planned: { icon: CalendarClock, variant: 'outline' },
    in_progress: { icon: FlaskConical, variant: 'default' },
    completed: { icon: CheckCircle2, variant: 'secondary' },
    cancelled: { icon: CircleSlash, variant: 'secondary' },
};

const ITERATION_FALLBACK: Record<IterationStatus, string> = {
    planned: 'Planned',
    in_progress: 'In progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

export function IterationStatusBadge({
    status,
    label,
}: {
    status: IterationStatus;
    label?: string;
}) {
    const { t } = useTranslation();
    const { icon: Icon, variant } = ITERATION[status];

    return (
        <Badge variant={variant} data-test={`iteration-status-${status}`}>
            <Icon className="size-3" />
            {label ?? t(ITERATION_FALLBACK[status])}
        </Badge>
    );
}

/**
 * How a cycle turned out.
 *
 * Only `failed` is drawn as destructive, and `inconclusive` deliberately is not: a cycle that did not settle
 * its question has still told the designer something, and colouring it as a failure would make the history
 * read worse than it was.
 */
const OUTCOME: Record<IterationOutcome, Presentation> = {
    success: { icon: CheckCircle2, variant: 'default' },
    partial: { icon: TriangleAlert, variant: 'secondary' },
    failed: { icon: XCircle, variant: 'destructive' },
    inconclusive: { icon: CircleHelp, variant: 'outline' },
};

const OUTCOME_FALLBACK: Record<IterationOutcome, string> = {
    success: 'Success',
    partial: 'Partial',
    failed: 'Failed',
    inconclusive: 'Inconclusive',
};

export function IterationOutcomeBadge({
    outcome,
    label,
}: {
    outcome: IterationOutcome;
    label?: string | null;
}) {
    const { t } = useTranslation();
    const { icon: Icon, variant } = OUTCOME[outcome];

    return (
        <Badge variant={variant} data-test={`iteration-outcome-${outcome}`}>
            <Icon className="size-3" />
            {label ?? t(OUTCOME_FALLBACK[outcome])}
        </Badge>
    );
}

const EXPERIMENT: Record<ExperimentStatus, Presentation> = {
    planned: { icon: CircleDashed, variant: 'outline' },
    running: { icon: FlaskConical, variant: 'default' },
    completed: { icon: CheckCircle2, variant: 'secondary' },
    cancelled: { icon: Ban, variant: 'secondary' },
};

const EXPERIMENT_FALLBACK: Record<ExperimentStatus, string> = {
    planned: 'Planned',
    running: 'Running',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

export function ExperimentStatusBadge({
    status,
    label,
}: {
    status: ExperimentStatus;
    label?: string;
}) {
    const { t } = useTranslation();
    const { icon: Icon, variant } = EXPERIMENT[status];

    return (
        <Badge variant={variant} data-test={`experiment-status-${status}`}>
            <Icon className="size-3" />
            {label ?? t(EXPERIMENT_FALLBACK[status])}
        </Badge>
    );
}

const DECISION: Record<DecisionStatus, Presentation> = {
    proposed: { icon: CircleDashed, variant: 'outline' },
    accepted: { icon: ThumbsUp, variant: 'default' },
    rejected: { icon: ThumbsDown, variant: 'secondary' },
    deferred: { icon: PauseCircle, variant: 'outline' },
};

const DECISION_FALLBACK: Record<DecisionStatus, string> = {
    proposed: 'Proposed',
    accepted: 'Accepted',
    rejected: 'Rejected',
    deferred: 'Deferred',
};

export function DecisionStatusBadge({
    status,
    label,
}: {
    status: DecisionStatus;
    label?: string;
}) {
    const { t } = useTranslation();
    const { icon: Icon, variant } = DECISION[status];

    return (
        <Badge variant={variant} data-test={`decision-status-${status}`}>
            <Icon className="size-3" />
            {label ?? t(DECISION_FALLBACK[status])}
        </Badge>
    );
}
