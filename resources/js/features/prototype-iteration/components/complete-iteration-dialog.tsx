import { CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { completeIteration } from '../api';
import { useDesignForm } from '../hooks/use-design-form';
import {
    emptyCompleteIterationInput,
    ITERATION_SUMMARY_MAX_LENGTH,
    ITERATION_SUMMARY_MIN_LENGTH,
    validateLength,
} from '../schemas/prototype-iteration';
import type {
    Iteration,
    IterationOptions,
    IterationOutcome,
} from '../types/prototype-iteration';

type CompleteIterationDialogProps = {
    workspace: string;
    game: string;
    iteration: Iteration;
    options: IterationOptions;
};

/**
 * Closes a design cycle.
 *
 * A dialog rather than a button, because completion is the one lifecycle action that requires the designer to
 * say something first: an outcome and a summary, both, with no default for either. An outcome that fell back
 * to something would record the platform's guess as the studio's own judgement.
 *
 * The outcomes carry their descriptions, because the difference between "failed" and "inconclusive" is the one
 * people get wrong — and it is picked in a hurry at the end of a cycle.
 *
 * The warning underneath is what makes the rest of the module's read-only behaviour comprehensible rather than
 * surprising. Somebody should know before they press it that this cycle is about to become history.
 */
export default function CompleteIterationDialog({
    workspace,
    game,
    iteration,
    options,
}: CompleteIterationDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useDesignForm({
        initial: emptyCompleteIterationInput,
        validate: (input) => ({
            outcome:
                input.outcome === ''
                    ? t('Say how the iteration turned out.')
                    : undefined,
            summary:
                validateLength(input.summary, {
                    min: ITERATION_SUMMARY_MIN_LENGTH,
                    max: ITERATION_SUMMARY_MAX_LENGTH,
                    tooShort: t(
                        'Say what you learned, so the next iteration can start from it.',
                    ),
                    tooLong: t('That summary is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            completeIteration(
                { workspace, game },
                iteration.id,
                input,
                mutation,
            ),
        resetOnSuccess: false,
        onSuccess: () => setOpen(false),
    });

    const close = (next: boolean) => {
        setOpen(next);

        if (!next) {
            form.reset();
        }
    };

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogTrigger asChild>
                <Button data-test="complete-iteration-button">
                    <CheckCircle2 className="size-4" />
                    {t('Complete iteration')}
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{t('Complete iteration')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'How did it turn out, and what did you learn? This is what the next iteration starts from.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit();
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="iteration-outcome">
                            {t('Outcome')}
                        </Label>

                        <Select
                            value={form.input.outcome}
                            onValueChange={(value) =>
                                form.setField(
                                    'outcome',
                                    value as IterationOutcome,
                                )
                            }
                        >
                            <SelectTrigger
                                id="iteration-outcome"
                                data-test="iteration-outcome-picker"
                            >
                                <SelectValue
                                    placeholder={t('Choose an outcome')}
                                />
                            </SelectTrigger>

                            <SelectContent>
                                {options.outcomes.map((outcome) => (
                                    <SelectItem
                                        key={outcome.value}
                                        value={outcome.value}
                                    >
                                        {outcome.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <p className="text-xs text-muted-foreground">
                            {options.outcomes.find(
                                (outcome) =>
                                    outcome.value === form.input.outcome,
                            )?.description ??
                                t('Four readings of how the cycle went.')}
                        </p>

                        <InputError message={form.errors.outcome} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="iteration-summary">
                            {t('What did you learn?')}
                        </Label>

                        <Textarea
                            id="iteration-summary"
                            value={form.input.summary}
                            onChange={(event) =>
                                form.setField('summary', event.target.value)
                            }
                            placeholder={t(
                                'Combat became more interesting, but downtime remains too high.',
                            )}
                            rows={4}
                            data-test="iteration-summary-input"
                        />

                        <InputError message={form.errors.summary} />
                    </div>

                    <p
                        className="rounded-md border border-dashed p-3 text-xs text-muted-foreground"
                        data-test="complete-iteration-warning"
                    >
                        {t(
                            'Completing an iteration makes it part of the design history. Its objective, changes and decisions become read-only — record a follow-up in a new iteration if anything changes later.',
                        )}
                    </p>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => close(false)}
                        >
                            {t('Cancel')}
                        </Button>

                        <Button
                            type="submit"
                            disabled={form.processing}
                            data-test="submit-complete-iteration-button"
                        >
                            {form.processing && <Spinner />}
                            {t('Complete iteration')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
