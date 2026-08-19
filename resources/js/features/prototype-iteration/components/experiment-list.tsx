import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import {
    cancelExperiment,
    completeExperiment,
    createExperiment,
    startExperiment,
} from '../api';
import { useDesignForm } from '../hooks/use-design-form';
import {
    emptyCompleteExperimentInput,
    emptyExperimentInput,
    EXPERIMENT_QUESTION_MAX_LENGTH,
    EXPERIMENT_QUESTION_MIN_LENGTH,
    EXPERIMENT_RESULT_MAX_LENGTH,
    EXPERIMENT_RESULT_MIN_LENGTH,
    EXPERIMENT_TITLE_MAX_LENGTH,
    EXPERIMENT_TITLE_MIN_LENGTH,
    validateLength,
} from '../schemas/prototype-iteration';
import type { DesignExperiment } from '../types/prototype-iteration';
import { ExperimentStatusBadge } from './status-badges';

type ExperimentListProps = {
    experiments: DesignExperiment[];
    workspace: string;
    game: string;
    iteration: string;
    canRecordWork: boolean;
};

/**
 * The focused questions this cycle set out to answer.
 *
 * Each experiment is drawn in two halves with a rule between them, and the rule is not decoration: the top
 * half was written before anything ran and the bottom half after. Keeping them visibly apart is what lets a
 * reader see that the prediction was made at risk — and the interface enforces the same split as the server,
 * because the result form only appears on an experiment that is actually running.
 *
 * A result with no conclusion is drawn as a result with no conclusion rather than as an incomplete record. It
 * is the state a real experiment sits in for days: the studio has seen what happened and has not yet decided
 * what it means.
 */
export default function ExperimentList({
    experiments,
    workspace,
    game,
    iteration,
    canRecordWork,
}: ExperimentListProps) {
    const { t } = useTranslation();

    const form = useDesignForm({
        initial: emptyExperimentInput,
        validate: (input) => ({
            title:
                validateLength(input.title, {
                    min: EXPERIMENT_TITLE_MIN_LENGTH,
                    max: EXPERIMENT_TITLE_MAX_LENGTH,
                    tooShort: t('Give the experiment a title.'),
                    tooLong: t('That title is too long.'),
                }) ?? undefined,
            question:
                validateLength(input.question, {
                    min: EXPERIMENT_QUESTION_MIN_LENGTH,
                    max: EXPERIMENT_QUESTION_MAX_LENGTH,
                    tooShort: t('Say what you want to find out.'),
                    tooLong: t('That question is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createExperiment({ workspace, game }, iteration, input, mutation),
    });

    return (
        <Card data-test="experiments">
            <CardHeader>
                <CardTitle className="text-base">{t('Experiments')}</CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {experiments.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed py-6 text-center text-sm text-muted-foreground"
                        data-test="experiments-empty"
                    >
                        {t('No experiments yet.')}
                    </p>
                ) : (
                    <ul className="space-y-3" data-test="experiment-list">
                        {experiments.map((experiment) => (
                            <ExperimentEntry
                                key={experiment.id}
                                experiment={experiment}
                                workspace={workspace}
                                game={game}
                                iteration={iteration}
                                canRecordWork={canRecordWork}
                            />
                        ))}
                    </ul>
                )}

                {canRecordWork && (
                    <form
                        className="grid gap-3 rounded-md border p-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.submit();
                        }}
                        data-test="create-experiment-form"
                    >
                        <div className="grid gap-1">
                            <Label
                                htmlFor="experiment-title"
                                className="text-xs"
                            >
                                {t('Title')}
                            </Label>

                            <Input
                                id="experiment-title"
                                value={form.input.title}
                                onChange={(event) =>
                                    form.setField('title', event.target.value)
                                }
                                placeholder={t('Four-player combat test')}
                                autoComplete="off"
                                data-test="experiment-title-input"
                            />

                            <InputError message={form.errors.title} />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor="experiment-question"
                                className="text-xs"
                            >
                                {t('What do you want to find out?')}
                            </Label>

                            <Textarea
                                id="experiment-question"
                                value={form.input.question}
                                onChange={(event) =>
                                    form.setField(
                                        'question',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'Does removing reactions reduce downtime?',
                                )}
                                rows={2}
                                data-test="experiment-question-input"
                            />

                            <InputError message={form.errors.question} />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor="experiment-hypothesis"
                                className="text-xs"
                            >
                                {t('What do you expect?')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Textarea
                                id="experiment-hypothesis"
                                value={form.input.hypothesis}
                                onChange={(event) =>
                                    form.setField(
                                        'hypothesis',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'Downtime will fall by about a fifth.',
                                )}
                                rows={2}
                            />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor="experiment-method"
                                className="text-xs"
                            >
                                {t('How will you test it?')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Textarea
                                id="experiment-method"
                                value={form.input.method}
                                onChange={(event) =>
                                    form.setField('method', event.target.value)
                                }
                                placeholder={t(
                                    'Run three four-player sessions with unlimited actions.',
                                )}
                                rows={2}
                            />
                        </div>

                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                size="sm"
                                disabled={form.processing}
                                data-test="submit-experiment-button"
                            >
                                {form.processing && <Spinner />}
                                {t('Add experiment')}
                            </Button>
                        </div>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * One experiment, in its two halves.
 */
function ExperimentEntry({
    experiment,
    workspace,
    game,
    iteration,
    canRecordWork,
}: {
    experiment: DesignExperiment;
    workspace: string;
    game: string;
    iteration: string;
    canRecordWork: boolean;
}) {
    const { t } = useTranslation();
    const [recording, setRecording] = useState(false);

    const offers = (status: string) =>
        experiment.available_transitions.some((move) => move.status === status);

    const results = useDesignForm({
        initial: emptyCompleteExperimentInput,
        validate: (input) => ({
            actual_result:
                validateLength(input.actual_result, {
                    min: EXPERIMENT_RESULT_MIN_LENGTH,
                    max: EXPERIMENT_RESULT_MAX_LENGTH,
                    tooShort: t('Say what actually happened.'),
                    tooLong: t('That result is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            completeExperiment(
                { workspace, game },
                iteration,
                experiment.id,
                input,
                mutation,
            ),
        onSuccess: () => setRecording(false),
    });

    return (
        <li
            className="space-y-2 rounded-md border p-3"
            data-test={`experiment-${experiment.id}`}
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <span className="text-sm font-medium" dir="auto">
                    {experiment.title}
                </span>

                <ExperimentStatusBadge
                    status={experiment.status}
                    label={experiment.status_label}
                />
            </div>

            <dl className="space-y-1 text-sm">
                <Field label={t('Question')} value={experiment.question} />
                <Field label={t('Expected')} value={experiment.hypothesis} />
                <Field label={t('Method')} value={experiment.method} />
            </dl>

            {(experiment.actual_result || experiment.conclusion) && (
                <dl className="space-y-1 border-t pt-2 text-sm">
                    <Field
                        label={t('What happened')}
                        value={experiment.actual_result}
                    />
                    <Field
                        label={t('What it means')}
                        value={experiment.conclusion}
                    />
                </dl>
            )}

            {canRecordWork && (
                <div className="flex flex-wrap items-center gap-2 pt-1">
                    {offers('running') && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                startExperiment(
                                    { workspace, game },
                                    iteration,
                                    experiment.id,
                                )
                            }
                            data-test={`start-experiment-${experiment.id}`}
                        >
                            {t('Start experiment')}
                        </Button>
                    )}

                    {offers('completed') && !recording && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setRecording(true)}
                            data-test={`record-result-${experiment.id}`}
                        >
                            {t('Record result')}
                        </Button>
                    )}

                    {offers('cancelled') && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                cancelExperiment(
                                    { workspace, game },
                                    iteration,
                                    experiment.id,
                                )
                            }
                            data-test={`cancel-experiment-${experiment.id}`}
                        >
                            {t('Cancel experiment')}
                        </Button>
                    )}
                </div>
            )}

            {recording && (
                <form
                    className="grid gap-2 border-t pt-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        results.submit();
                    }}
                    data-test={`complete-experiment-form-${experiment.id}`}
                >
                    <Label
                        htmlFor={`result-${experiment.id}`}
                        className="text-xs"
                    >
                        {t('What actually happened?')}
                    </Label>

                    <Textarea
                        id={`result-${experiment.id}`}
                        value={results.input.actual_result}
                        onChange={(event) =>
                            results.setField(
                                'actual_result',
                                event.target.value,
                            )
                        }
                        placeholder={t(
                            'Players explored more strategies but sessions ran twenty minutes longer.',
                        )}
                        rows={2}
                        data-test={`experiment-result-input-${experiment.id}`}
                    />

                    <InputError message={results.errors.actual_result} />

                    <Label
                        htmlFor={`conclusion-${experiment.id}`}
                        className="text-xs"
                    >
                        {t('What does it mean?')}{' '}
                        <span className="font-normal text-muted-foreground">
                            {t('(optional)')}
                        </span>
                    </Label>

                    <Textarea
                        id={`conclusion-${experiment.id}`}
                        value={results.input.conclusion}
                        onChange={(event) =>
                            results.setField('conclusion', event.target.value)
                        }
                        placeholder={t(
                            'Unlimited actions improve strategy but harm pacing.',
                        )}
                        rows={2}
                    />

                    <p className="text-xs text-muted-foreground">
                        {t(
                            'You can leave this until you have read the observations back.',
                        )}
                    </p>

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setRecording(false)}
                        >
                            {t('Cancel')}
                        </Button>

                        <Button
                            type="submit"
                            size="sm"
                            disabled={results.processing}
                            data-test={`submit-result-${experiment.id}`}
                        >
                            {results.processing && <Spinner />}
                            {t('Record result')}
                        </Button>
                    </div>
                </form>
            )}
        </li>
    );
}

/**
 * One labelled line of an experiment, rendered only when there is something to say.
 */
function Field({ label, value }: { label: string; value: string | null }) {
    if (!value) {
        return null;
    }

    return (
        <div className="flex gap-2">
            <dt className="shrink-0 text-xs text-muted-foreground">{label}</dt>
            <dd className="min-w-0" dir="auto">
                {value}
            </dd>
        </div>
    );
}
