import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import phases from '@/routes/games/framework/phases';
import type { DesignPhase, PhaseProgress } from '../types/framework';

type PhaseNavProps = {
    workspace: string;
    game: string;
    phases: DesignPhase[];
    progress: PhaseProgress[];
    currentSlug?: string;
};

/**
 * The arc of a methodology, with this game's place in it.
 *
 * Phases are addressed by slug rather than by id, because the URL is resolved
 * through the edition this game adopted — `…/framework/phases/core-loop`
 * reaches v1's core loop phase for a game on v1, whatever v2 renamed or split.
 *
 * A phase with nothing countable in it shows no figure at all. Drawing "100%"
 * against a phase that is entirely principles would be the interface claiming
 * work was done when there was none to do.
 *
 * Each phase is a bordered row rather than a card, because a card carries
 * `py-6` of its own and an arc of ten of them is a page in its own right. This
 * is a table of contents; it should cost a glance, not a scroll.
 *
 * The name stays on one line and the description wraps. A phase name is an
 * address — the thing being scanned for — and clipping it would make two
 * stages of an arc look alike; the sentence under it is what says whether this
 * is the stage you want, and is no use cut off at the first clause.
 *
 * Every row is the same height. `auto-rows-fr` sizes them all to the tallest
 * rather than to their own content, so a wrapping description does not make
 * one stage of the arc look more substantial than the next — the rows are a
 * list of equals, and ragged ones read as a ranking.
 */
export default function PhaseNav({
    workspace,
    game,
    phases: arc,
    progress,
    currentSlug,
}: PhaseNavProps) {
    const byPhase = new Map(progress.map((entry) => [entry.phase_id, entry]));

    if (arc.length === 0) {
        return (
            <p
                className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground"
                data-test="phase-nav-empty"
            >
                This edition has no phases yet.
            </p>
        );
    }

    return (
        <nav
            className="grid auto-rows-fr gap-1"
            aria-label="Framework phases"
            data-test="phase-nav"
        >
            {arc.map((phase) => {
                const stats = byPhase.get(phase.id);
                const current = phase.slug === currentSlug;

                return (
                    <Link
                        key={phase.id}
                        href={phases.show.url({
                            workspace,
                            game,
                            phase: phase.slug,
                        })}
                        className={cn(
                            'flex items-start justify-between gap-3 rounded-md border px-3 py-2 transition-colors hover:border-primary/40 hover:bg-muted/50',
                            current && 'border-primary bg-muted/50',
                        )}
                        data-test={`phase-link-${phase.slug}`}
                    >
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-medium">
                                {phase.position}. {phase.name}
                            </span>

                            {phase.description && (
                                <span className="mt-0.5 block text-xs text-muted-foreground">
                                    {phase.description}
                                </span>
                            )}
                        </span>

                        {stats && !stats.is_empty && (
                            <span className="inline-flex shrink-0 items-center gap-1.5 text-xs text-muted-foreground">
                                {stats.is_complete && (
                                    <Check className="size-3.5 text-primary" />
                                )}
                                {stats.percentage}%
                            </span>
                        )}
                    </Link>
                );
            })}
        </nav>
    );
}
