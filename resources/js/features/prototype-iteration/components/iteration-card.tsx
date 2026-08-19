import { Link } from '@inertiajs/react';
import {
    Boxes,
    FlaskConical,
    GitBranch,
    PenLine,
    Scale,
    TestTubes,
} from 'lucide-react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import iterations from '@/routes/iterations';
import type { IterationCard as IterationCardData } from '../types/prototype-iteration';
import { IterationOutcomeBadge, IterationStatusBadge } from './status-badges';

type IterationCardProps = {
    iteration: IterationCardData;
    workspace: string;
    game: string;
};

/**
 * One design cycle in a list.
 *
 * Shows the objective rather than the hypothesis, which is the opposite of the choice a playtest card makes.
 * A playtest list is scanned for "what were we trying to find out?", where a hypothesis is the sharper line;
 * an iterations list is scanned for "what were we trying to fix?", and that is the objective.
 *
 * The four counts underneath are what tell a reader how substantial the cycle was without opening it — and,
 * read down a column, they are the shape of how a studio works.
 */
export default function IterationCard({
    iteration,
    workspace,
    game,
}: IterationCardProps) {
    const { choice } = useTranslation();

    const build = [iteration.prototype_name, iteration.prototype_version_label]
        .filter(Boolean)
        .join(' · ');

    return (
        <Card className="transition-colors hover:border-primary/40">
            <CardHeader className="gap-2">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Link
                        href={iterations.show.url({
                            workspace,
                            game,
                            iteration: iteration.id,
                        })}
                        className="min-w-0 font-medium hover:underline"
                        data-test={`iteration-link-${iteration.id}`}
                        dir="auto"
                    >
                        {iteration.title}
                    </Link>

                    <div className="flex flex-wrap items-center gap-2">
                        <IterationStatusBadge
                            status={iteration.status}
                            label={iteration.status_label}
                        />

                        {iteration.outcome && (
                            <IterationOutcomeBadge
                                outcome={iteration.outcome}
                                label={iteration.outcome_label}
                            />
                        )}
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-3">
                <p
                    className="line-clamp-2 text-sm text-muted-foreground"
                    dir="auto"
                >
                    {iteration.objective}
                </p>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    {iteration.version_label && (
                        <span className="inline-flex items-center gap-1">
                            <GitBranch className="size-3" />
                            {iteration.version_label}
                        </span>
                    )}

                    {build && (
                        <span
                            className="inline-flex items-center gap-1"
                            dir="auto"
                        >
                            <Boxes className="size-3" />
                            {build}
                        </span>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span className="inline-flex items-center gap-1">
                        <PenLine className="size-3" />
                        {choice(
                            ':count change|:count changes',
                            iteration.changes_count ?? 0,
                        )}
                    </span>

                    <span className="inline-flex items-center gap-1">
                        <TestTubes className="size-3" />
                        {choice(
                            ':count experiment|:count experiments',
                            iteration.experiments_count ?? 0,
                        )}
                    </span>

                    <span className="inline-flex items-center gap-1">
                        <Scale className="size-3" />
                        {choice(
                            ':count decision|:count decisions',
                            iteration.decisions_count ?? 0,
                        )}
                    </span>

                    <span className="inline-flex items-center gap-1">
                        <FlaskConical className="size-3" />
                        {choice(
                            ':count playtest|:count playtests',
                            iteration.playtests_count ?? 0,
                        )}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}
