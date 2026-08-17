import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
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
            className="grid gap-2"
            aria-label="Framework phases"
            data-test="phase-nav"
        >
            {arc.map((phase) => {
                const stats = byPhase.get(phase.id);
                const current = phase.slug === currentSlug;

                return (
                    <Card
                        key={phase.id}
                        className={cn(
                            'transition-colors hover:border-primary/40',
                            current && 'border-primary',
                        )}
                    >
                        <CardContent className="py-3">
                            <Link
                                href={phases.show.url({
                                    workspace,
                                    game,
                                    phase: phase.slug,
                                })}
                                className="flex items-center justify-between gap-3"
                                data-test={`phase-link-${phase.slug}`}
                            >
                                <span className="min-w-0">
                                    <span className="block truncate text-sm font-medium">
                                        {phase.position}. {phase.name}
                                    </span>

                                    {phase.description && (
                                        <span className="block truncate text-xs text-muted-foreground">
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
                        </CardContent>
                    </Card>
                );
            })}
        </nav>
    );
}
