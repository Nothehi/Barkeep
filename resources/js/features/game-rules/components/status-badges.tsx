import { Badge } from '@/components/ui/badge';
import type {
    ReferenceType,
    RuleSetStatus,
    RuleStatus,
    ValidationSeverity,
} from '../types/game-rules';

/**
 * The badges the rules screens draw.
 *
 * Every one of them takes the label as a prop rather than deriving it: the wording lives on an enum in the
 * domain and travels with the record, so a status renamed there reads the new way here without anything in
 * TypeScript changing. What these decide is only the *colour*, which is a presentation question the server
 * has no opinion about.
 */

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

type StatusBadgeProps<TStatus> = {
    status: TStatus;
    label: string;
};

/**
 * Where a rule system sits in its own life.
 *
 * Active is the only one drawn solid, because "these are the rules now" is the single most important fact on
 * a screen that may list four rule sets.
 */
export function RuleSetStatusBadge({
    status,
    label,
}: StatusBadgeProps<RuleSetStatus>) {
    const variants: Record<RuleSetStatus, BadgeVariant> = {
        draft: 'outline',
        active: 'default',
        archived: 'secondary',
    };

    return <Badge variant={variants[status]}>{label}</Badge>;
}

/**
 * How settled one rule, phase or action is.
 *
 * Active draws nothing at all — it is the ordinary case, and a badge on every row would be noise on a screen
 * of forty. Only draft and deprecated are worth saying.
 */
export function RuleStatusBadge({
    status,
    label,
}: StatusBadgeProps<RuleStatus>) {
    if (status === 'active') {
        return null;
    }

    return (
        <Badge variant={status === 'deprecated' ? 'secondary' : 'outline'}>
            {label}
        </Badge>
    );
}

/**
 * How seriously to take something the validator found.
 */
export function SeverityBadge({
    severity,
    label,
}: StatusBadgeProps<ValidationSeverity> & { severity: ValidationSeverity }) {
    return (
        <Badge variant={severity === 'error' ? 'destructive' : 'outline'}>
            {label}
        </Badge>
    );
}

/**
 * How one rule relates to another.
 *
 * The directed kinds are drawn solid and "related to" is not, because the difference is the one a reader
 * needs at a glance: the first four say something about precedence, and the fifth is a note.
 */
export function ReferenceTypeBadge({
    type,
    label,
    isDirected,
}: {
    type: ReferenceType;
    label: string;
    isDirected: boolean;
}) {
    return (
        <Badge
            variant={isDirected ? 'secondary' : 'outline'}
            data-reference-type={type}
        >
            {label}
        </Badge>
    );
}
