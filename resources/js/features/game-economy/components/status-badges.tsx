import { Badge } from '@/components/ui/badge';
import type {
    BalanceProfileStatus,
    BalanceScenarioStatus,
    BalanceWarningSeverity,
    ObservationSeverity,
    ResourceFlowType,
    SnapshotChangeType,
} from '../types/game-economy';

/**
 * The badges the balance screens draw.
 *
 * Every one of them takes the label as a prop rather than deriving it: the wording lives on an enum in the
 * domain and travels with the record, so a category renamed there reads the new way here without anything
 * in TypeScript changing. What these decide is only the *colour*, which is a presentation question the
 * server has no opinion about.
 */

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

type StatusBadgeProps<TStatus> = {
    status: TStatus;
    label: string;
};

/**
 * Where a configuration sits in its own life.
 *
 * Active is the only one drawn solid, because "these are the numbers now" is the single most important fact
 * on a screen that may list four profiles.
 */
export function BalanceProfileStatusBadge({
    status,
    label,
}: StatusBadgeProps<BalanceProfileStatus>) {
    const variants: Record<BalanceProfileStatus, BadgeVariant> = {
        draft: 'outline',
        active: 'default',
        archived: 'secondary',
    };

    return <Badge variant={variants[status]}>{label}</Badge>;
}

export function BalanceScenarioStatusBadge({
    status,
    label,
}: StatusBadgeProps<BalanceScenarioStatus>) {
    const variants: Record<BalanceScenarioStatus, BadgeVariant> = {
        draft: 'outline',
        active: 'default',
        archived: 'secondary',
    };

    return <Badge variant={variants[status]}>{label}</Badge>;
}

/**
 * How a resource moves.
 *
 * Coloured by direction rather than by case, so a flow type added later is drawn correctly without this
 * file changing — the server already sends `direction`, and a source, a sink and a movement are the three
 * things a reader is actually distinguishing.
 */
export function FlowTypeBadge({
    flowType,
    label,
    direction,
}: {
    flowType: ResourceFlowType;
    label: string;
    direction: number;
}) {
    const tone =
        direction > 0
            ? 'border-emerald-500/40 text-emerald-600 dark:text-emerald-400'
            : direction < 0
              ? 'border-amber-500/40 text-amber-600 dark:text-amber-400'
              : 'border-sky-500/40 text-sky-600 dark:text-sky-400';

    return (
        <Badge
            variant="outline"
            className={tone}
            data-test={`flow-${flowType}`}
        >
            {label}
        </Badge>
    );
}

/**
 * How seriously to take something the analysis found.
 *
 * An error is the only one drawn as destructive. That restraint is deliberate: a half-built economy is full
 * of warnings, and a screen that shouted about all of them would train designers to stop reading.
 */
export function WarningSeverityBadge({
    severity,
    label,
}: {
    severity: BalanceWarningSeverity;
    label: string;
}) {
    const variants: Record<BalanceWarningSeverity, BadgeVariant> = {
        info: 'outline',
        warning: 'secondary',
        error: 'destructive',
    };

    return (
        <Badge variant={variants[severity]} data-test={`severity-${severity}`}>
            {label}
        </Badge>
    );
}

/**
 * How badly an observation reflects on the economy.
 */
export function ObservationSeverityBadge({
    severity,
    label,
}: {
    severity: ObservationSeverity;
    label: string;
}) {
    const variants: Record<ObservationSeverity, BadgeVariant> = {
        info: 'outline',
        low: 'outline',
        medium: 'secondary',
        high: 'destructive',
        critical: 'destructive',
    };

    return <Badge variant={variants[severity]}>{label}</Badge>;
}

/**
 * What happened to one record between two snapshots.
 */
export function SnapshotChangeBadge({
    type,
    label,
}: {
    type: SnapshotChangeType;
    label: string;
}) {
    const tone: Record<SnapshotChangeType, string> = {
        added: 'border-emerald-500/40 text-emerald-600 dark:text-emerald-400',
        removed: 'border-rose-500/40 text-rose-600 dark:text-rose-400',
        changed: 'border-sky-500/40 text-sky-600 dark:text-sky-400',
    };

    return (
        <Badge variant="outline" className={tone[type]}>
            {label}
        </Badge>
    );
}
