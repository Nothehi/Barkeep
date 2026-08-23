import { AlertTriangle, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import type { MutationOptions } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyOutcomeInput,
    SHORT_DESCRIPTION_MAX_LENGTH,
    STATEMENT_NAME_MAX_LENGTH,
    STATEMENT_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type { OutcomeInput } from '../schemas/game-rules';
import type { Outcome, RuleCondition } from '../types/game-rules';
import OptionSelect from './option-select';

type OutcomeEditorProps = {
    heading: string;
    emptyMessage: string;
    addLabel: string;
    placeholder: string;
    outcomes: Outcome[];
    conditions: RuleCondition[];
    scope: RuleSetScope;
    canEdit: boolean;
    create: (
        scope: RuleSetScope,
        input: OutcomeInput,
        options: MutationOptions,
    ) => void;
    remove: (id: string) => void;
};

/**
 * How a game is won, lost, or brought to a close.
 *
 * One component for the three, and the three call sites differ only in their wording and which endpoint they
 * hit. The *records* stay separate on the server because winning, losing and stopping are three different
 * questions a game answers at once — but the form for them is identical, and three copies of it would be
 * three places for a length limit to drift.
 *
 * The condition is optional, and the list marks the ones without one rather than hiding them. Most outcomes
 * are written long before they are defined: "whoever has the most points" goes in on day one, and the
 * measurement comes later, if at all.
 */
export default function OutcomeEditor({
    heading,
    emptyMessage,
    addLabel,
    placeholder,
    outcomes,
    conditions,
    scope,
    canEdit,
    create,
    remove,
}: OutcomeEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: emptyOutcomeInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: STATEMENT_NAME_MIN_LENGTH,
                    max: STATEMENT_NAME_MAX_LENGTH,
                    tooShort: t('Say what happens.'),
                    tooLong: t('That is too long.'),
                }) ?? undefined,
            description:
                validateLength(input.description, {
                    max: SHORT_DESCRIPTION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That description is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) => create(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{heading}</CardTitle>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button size="sm" variant="outline">
                                <Plus className="size-4" />
                                {addLabel}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{addLabel}</DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'The condition can come later — writing it down first is the point.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="outcome-name">
                                        {t('What happens')}
                                    </Label>
                                    <Input
                                        id="outcome-name"
                                        value={form.input.name}
                                        dir="auto"
                                        placeholder={placeholder}
                                        onChange={(event) =>
                                            form.setField(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="outcome-condition">
                                        {t('Measured by')}
                                    </Label>
                                    <OptionSelect
                                        id="outcome-condition"
                                        value={form.input.condition_id}
                                        options={conditions.map(
                                            (condition) => ({
                                                value: condition.id,
                                                label: condition.statement,
                                            }),
                                        )}
                                        emptyLabel={t(
                                            'Not stated precisely yet',
                                        )}
                                        onChange={(value) =>
                                            form.setField('condition_id', value)
                                        }
                                    />
                                    <InputError
                                        message={form.errors.condition_id}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="outcome-description">
                                        {t('Notes')}
                                    </Label>
                                    <Textarea
                                        id="outcome-description"
                                        rows={2}
                                        value={form.input.description}
                                        dir="auto"
                                        onChange={(event) =>
                                            form.setField(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.description}
                                    />
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    variant="ghost"
                                    onClick={() => setOpen(false)}
                                    disabled={form.processing}
                                >
                                    {t('Cancel')}
                                </Button>

                                <Button
                                    onClick={form.submit}
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    {addLabel}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </CardHeader>

            <CardContent>
                {outcomes.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {emptyMessage}
                    </p>
                ) : (
                    <ol className="space-y-2">
                        {outcomes.map((outcome, index) => (
                            <li
                                key={outcome.id}
                                className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2"
                            >
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    {index + 1}.
                                </span>

                                <span className="text-sm" dir="auto">
                                    {outcome.name}
                                </span>

                                {outcome.condition_statement ? (
                                    <Badge variant="secondary" dir="auto">
                                        {outcome.condition_statement}
                                    </Badge>
                                ) : (
                                    <Badge variant="outline" className="gap-1">
                                        <AlertTriangle className="size-3" />
                                        {t('Not measurable yet')}
                                    </Badge>
                                )}

                                {canEdit && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="ms-auto"
                                        aria-label={t('Remove')}
                                        onClick={() => remove(outcome.id)}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
