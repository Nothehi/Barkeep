import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { evaluateCriterion } from '../api';
import type {
    CriterionEvaluation,
    CriterionRating,
    DesignCriterion,
    RatingOption,
} from '../types/framework';

type CriterionListProps = {
    workspace: string;
    game: string;
    criteria: DesignCriterion[];
    evaluations: CriterionEvaluation[];
    ratings: RatingOption[];
    canRecord: boolean;
};

/**
 * The questions this phase asks of the design, and how this game answers them.
 *
 * The two arrive as separate collections and are joined here by id. That is
 * the module's central separation showing through to the interface: the
 * criterion is asked of everybody following the edition, and the grade belongs
 * to exactly one project.
 *
 * The grades are the server's, with their descriptions, because the difference
 * between "weak" and "needs work" is not self-evident — and because a client
 * that hard-coded the scale would be a second opinion waiting to go stale.
 *
 * There is no "not evaluated" button. It is the state a criterion is in before
 * anybody acts, and offering it would make clearing an assessment look like
 * making one.
 */
export default function CriterionList({
    workspace,
    game,
    criteria,
    evaluations,
    ratings,
    canRecord,
}: CriterionListProps) {
    const byCriterion = new Map(
        evaluations.map((evaluation) => [evaluation.criterion_id, evaluation]),
    );

    if (criteria.length === 0) {
        return null;
    }

    return (
        <section className="space-y-3" data-test="criterion-list">
            <h2 className="text-sm font-medium">Criteria</h2>

            <div className="grid gap-3">
                {criteria.map((criterion) => (
                    <CriterionRow
                        key={criterion.id}
                        workspace={workspace}
                        game={game}
                        criterion={criterion}
                        evaluation={byCriterion.get(criterion.id) ?? null}
                        ratings={ratings}
                        canRecord={canRecord}
                    />
                ))}
            </div>
        </section>
    );
}

type CriterionRowProps = {
    workspace: string;
    game: string;
    criterion: DesignCriterion;
    evaluation: CriterionEvaluation | null;
    ratings: RatingOption[];
    canRecord: boolean;
};

/**
 * One question, and this game's standing answer to it.
 *
 * The note is only opened when there is something to say or something already
 * said. A grade is one press; being made to write a justification for every
 * one is how a self-assessment tool stops being used.
 */
function CriterionRow({
    workspace,
    game,
    criterion,
    evaluation,
    ratings,
    canRecord,
}: CriterionRowProps) {
    const [pending, setPending] = useState<CriterionRating | null>(null);
    const [notes, setNotes] = useState(evaluation?.notes ?? '');
    const [editingNotes, setEditingNotes] = useState(false);

    const grade = (rating: RatingOption['value']) => {
        setPending(rating);

        evaluateCriterion(
            workspace,
            game,
            criterion.id,
            rating as Exclude<CriterionRating, 'not_evaluated'>,
            notes.trim() || null,
            {
                onSuccess: () => setEditingNotes(false),
                onFinish: () => setPending(null),
            },
        );
    };

    return (
        <Card data-test={`criterion-${criterion.id}`}>
            <CardHeader className="gap-1">
                <span className="font-medium">{criterion.title}</span>

                {criterion.description && (
                    <span className="text-sm text-muted-foreground">
                        {criterion.description}
                    </span>
                )}
            </CardHeader>

            <CardContent className="space-y-3">
                <div className="flex flex-wrap items-center gap-2">
                    {ratings.map((rating) => {
                        const chosen = evaluation?.status === rating.value;

                        return (
                            <Tooltip key={rating.value}>
                                <TooltipTrigger asChild>
                                    <Button
                                        size="sm"
                                        variant={chosen ? 'default' : 'outline'}
                                        disabled={
                                            !canRecord || pending !== null
                                        }
                                        onClick={() => grade(rating.value)}
                                        className={cn(
                                            !canRecord && 'pointer-events-none',
                                        )}
                                        data-test={`grade-${criterion.id}-${rating.value}`}
                                    >
                                        {pending === rating.value && (
                                            <Spinner />
                                        )}
                                        {rating.label}
                                    </Button>
                                </TooltipTrigger>

                                <TooltipContent>
                                    {rating.description}
                                </TooltipContent>
                            </Tooltip>
                        );
                    })}
                </div>

                {canRecord && (editingNotes || notes !== '') ? (
                    <div className="space-y-2">
                        <Textarea
                            value={notes}
                            onChange={(event) => setNotes(event.target.value)}
                            onFocus={() => setEditingNotes(true)}
                            placeholder="Why this grade?"
                            rows={2}
                            data-test={`criterion-notes-${criterion.id}`}
                        />

                        {editingNotes && evaluation && (
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={pending !== null}
                                onClick={() => grade(evaluation.status)}
                            >
                                Save note
                            </Button>
                        )}
                    </div>
                ) : (
                    canRecord && (
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => setEditingNotes(true)}
                            data-test={`criterion-add-note-${criterion.id}`}
                        >
                            Add a note
                        </Button>
                    )
                )}

                {evaluation === null && (
                    <p className="text-xs text-muted-foreground">
                        Not assessed yet.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
