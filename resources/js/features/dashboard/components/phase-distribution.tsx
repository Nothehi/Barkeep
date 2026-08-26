import { useFormatters } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { PhaseTally } from '../types/dashboard';

type PhaseDistributionProps = {
    phases: PhaseTally[];
};

/**
 * Where a studio's games are along the design arc.
 *
 * Every phase is drawn, including the empty ones, because the shape is the
 * point: eight games clustered in "concept" and none past "prototyping" is a
 * studio that keeps starting things, and that only reads if the phases it has
 * not reached are visible. A list of the three phases somebody happens to be
 * in would hide exactly the interesting part.
 *
 * The bars are scaled to the busiest phase rather than to the total, so a
 * workspace where one phase holds most of the work still shows a readable
 * difference between the rest.
 */
export default function PhaseDistribution({ phases }: PhaseDistributionProps) {
    const { formatNumber } = useFormatters();

    const busiest = Math.max(...phases.map((phase) => phase.count), 1);

    return (
        <ul className="space-y-2">
            {phases.map((phase) => (
                <li key={phase.value} className="flex items-center gap-3">
                    <span
                        className={cn(
                            'w-28 shrink-0 truncate text-xs',
                            phase.count > 0
                                ? 'text-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        {phase.label}
                    </span>

                    <span
                        className="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                        role="presentation"
                    >
                        <span
                            className="block h-full rounded-full bg-primary/70"
                            style={{
                                width: `${(phase.count / busiest) * 100}%`,
                            }}
                        />
                    </span>

                    {/*
                     * No label of its own: the row already reads as "Idea 0"
                     * from its two pieces of real text, and an aria-label on a
                     * generic span is ignored by screen readers anyway. The bar
                     * between them is presentational — it restates the number
                     * rather than adding to it.
                     */}
                    <span
                        className={cn(
                            'w-6 shrink-0 text-end text-xs tabular-nums',
                            phase.count > 0
                                ? 'text-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        {formatNumber(phase.count)}
                    </span>
                </li>
            ))}
        </ul>
    );
}
