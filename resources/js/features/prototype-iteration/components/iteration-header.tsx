import { CalendarCheck, CalendarClock, Boxes, GitBranch } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { cancelIteration, startIteration } from '../api';
import { useIterationPermissions } from '../hooks/use-permissions';
import type { Iteration } from '../types/prototype-iteration';
import CompleteIterationDialog from './complete-iteration-dialog';
import { IterationOutcomeBadge, IterationStatusBadge } from './status-badges';

type IterationHeaderProps = {
    iteration: Iteration;
    workspace: string;
    game: string;
    options: Parameters<typeof CompleteIterationDialog>[0]['options'];
};

/**
 * The heading of a design cycle: what it is about, and what can be done to it.
 *
 * The buttons are drawn from `available_transitions`, which the server derives from the lifecycle matrix per
 * iteration and per caller. The client renders the moves it is given rather than deciding which are legal —
 * so a completed cycle simply has no buttons, and nobody has to remember to hide them.
 *
 * Completion is the exception: it opens a dialog rather than posting, because it is the one lifecycle action
 * that requires the designer to say something first.
 */
export default function IterationHeader({
    iteration,
    workspace,
    game,
    options,
}: IterationHeaderProps) {
    const { t } = useTranslation();
    const { formatDate } = useFormatters();
    const permissions = useIterationPermissions(iteration);

    const offersStart = iteration.available_transitions.some(
        (move) => move.status === 'in_progress',
    );
    const offersComplete = iteration.available_transitions.some(
        (move) => move.status === 'completed',
    );
    const offersCancel = iteration.available_transitions.some(
        (move) => move.status === 'cancelled',
    );

    const build = [
        iteration.prototype_version?.prototype_name,
        iteration.prototype_version?.label,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <header className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 space-y-2">
                    <h1
                        className="text-xl font-semibold tracking-tight"
                        dir="auto"
                    >
                        {iteration.title}
                    </h1>

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

                <div className="flex flex-wrap items-center gap-2">
                    {offersStart && permissions.canStart && (
                        <Button
                            onClick={() =>
                                startIteration(
                                    { workspace, game },
                                    iteration.id,
                                )
                            }
                            data-test="start-iteration-button"
                        >
                            {t('Start iteration')}
                        </Button>
                    )}

                    {offersComplete && permissions.canComplete && (
                        <CompleteIterationDialog
                            workspace={workspace}
                            game={game}
                            iteration={iteration}
                            options={options}
                        />
                    )}

                    {offersCancel && permissions.canCancel && (
                        <Button
                            variant="outline"
                            onClick={() =>
                                cancelIteration(
                                    { workspace, game },
                                    iteration.id,
                                )
                            }
                            data-test="cancel-iteration-button"
                        >
                            {t('Cancel iteration')}
                        </Button>
                    )}
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                {build && (
                    <span className="inline-flex items-center gap-1" dir="auto">
                        <Boxes className="size-3" />
                        {build}
                    </span>
                )}

                {iteration.version?.label && (
                    <span className="inline-flex items-center gap-1">
                        <GitBranch className="size-3" />
                        {iteration.version.label}
                    </span>
                )}

                {iteration.started_at && (
                    <span className="inline-flex items-center gap-1">
                        <CalendarClock className="size-3" />
                        {t('Started :date', {
                            date: formatDate(iteration.started_at),
                        })}
                    </span>
                )}

                {iteration.completed_at && (
                    <span className="inline-flex items-center gap-1">
                        <CalendarCheck className="size-3" />
                        {t('Completed :date', {
                            date: formatDate(iteration.completed_at),
                        })}
                    </span>
                )}
            </div>
        </header>
    );
}
