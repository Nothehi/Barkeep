import { cn } from '@/lib/utils';
import type { ProgressRatio } from '../types/framework';

type ProgressBarProps = {
    label: string;
    ratio: ProgressRatio;

    /**
     * Say that this figure is reported beside the total rather than counted
     * into it. Used for prompts, which have no right answer.
     */
    uncounted?: boolean;

    className?: string;
};

/**
 * How much of one countable set a game has dealt with.
 *
 * Every number here is the server's. The percentage in particular is never
 * recomputed from `completed / total` on the client: there is one definition
 * of framework progress, it lives in `FrameworkProgressCalculator`, and a
 * second one drawn in a bar would be a figure nobody can reconcile with the
 * one on the phase beside it.
 *
 * An empty set draws an empty bar rather than a full one. Nothing to do is not
 * the same as everything done, and a phase with no criteria claiming a hundred
 * per cent is how a progress screen loses a designer's trust.
 */
export default function ProgressBar({
    label,
    ratio,
    uncounted = false,
    className,
}: ProgressBarProps) {
    const empty = ratio.total === 0;

    return (
        <div className={cn('space-y-1.5', className)}>
            <div className="flex items-baseline justify-between gap-2">
                <span className="text-sm font-medium">
                    {label}
                    {uncounted && (
                        <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                            not counted
                        </span>
                    )}
                </span>

                <span className="text-xs text-muted-foreground">
                    {empty
                        ? 'Nothing to do'
                        : `${ratio.completed} of ${ratio.total}`}
                </span>
            </div>

            <div
                className="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-valuenow={ratio.percentage}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={`${label}: ${ratio.completed} of ${ratio.total}`}
            >
                <div
                    className={cn(
                        'h-full rounded-full transition-all',
                        ratio.is_complete && !empty
                            ? 'bg-primary'
                            : 'bg-foreground/40',
                    )}
                    style={{ width: `${empty ? 0 : ratio.percentage}%` }}
                />
            </div>
        </div>
    );
}
