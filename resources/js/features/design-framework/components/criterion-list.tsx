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
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { evaluateCriterion } from '../api';
import type {
    CriterionEvaluation,
    CriterionRating,
    DesignCriterion,
    GameDesignFacts,
    RatingOption,
} from '../types/framework';
import AnsweredFromDesign from './answered-from-design';

type CriterionListProps = {
    workspace: string;
    game: string;
    criteria: DesignCriterion[];
    evaluations: CriterionEvaluation[];
    ratings: RatingOption[];
    design: GameDesignFacts;
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
 *
 * A criterion that names a fact of the game's design gets no buttons at all. It
 * is answered from the design record, and the row says where — because grading
 * yourself on whether you have written your player count down was always the
 * wrong question to be asked.
 */
export default function CriterionList({
    workspace,
    game,
    criteria,
    evaluations,
    ratings,
    design,
    canRecord,
}: CriterionListProps) {
    const { t } = useTranslation();

    const byCriterion = new Map(
        evaluations.map((evaluation) => [evaluation.criterion_id, evaluation]),
    );

    if (criteria.length === 0) {
        return null;
    }

    return (
        <section className="space-y-3" data-test="criterion-list">
            <h2 className="text-sm font-medium">{t('Criteria')}</h2>

            <div className="grid gap-3">
                {criteria.map((criterion) => (
                    <CriterionRow
                        key={criterion.id}
                        workspace={workspace}
                        game={game}
                        criterion={criterion}
                        evaluation={byCriterion.get(criterion.id) ?? null}
                        ratings={ratings}
                        design={design}
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
    design: GameDesignFacts;
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
    design,
    canRecord,
}: CriterionRowProps) {
    const { t } = useTranslation();
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

    const header = (
        <CardHeader className="gap-1">
            <span className="font-medium" dir="auto">
                {criterion.title}
            </span>

            {criterion.description && (
                <span className="text-sm text-muted-foreground" dir="auto">
                    {criterion.description}
                </span>
            )}
        </CardHeader>
    );

    /*
     * Answered from the design record, so there is nothing to grade and nothing
     * to write a note about. The row says where the answer comes from and links
     * to where it is recorded — the work is "go and decide the player count",
     * not "have an opinion about whether you did".
     */
    if (criterion.is_answered_by_the_design_record) {
        return (
            <Card data-test={`criterion-${criterion.id}`}>
                {header}

                <CardContent>
                    <AnsweredFromDesign
                        label={criterion.satisfied_by_label ?? t('this')}
                        recorded={
                            design.facts[criterion.satisfied_by ?? ''] ?? false
                        }
                        settingsUrl={design.settings_url}
                    />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card data-test={`criterion-${criterion.id}`}>
            {header}

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
                            placeholder={t('Why this grade?')}
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
                                {t('Save note')}
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
                            {t('Add a note')}
                        </Button>
                    )
                )}

                {evaluation === null && (
                    <p className="text-xs text-muted-foreground">
                        {t('Not assessed yet.')}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
