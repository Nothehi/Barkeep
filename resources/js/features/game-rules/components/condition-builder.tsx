import { Plus, Trash2 } from 'lucide-react';
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
import { useTranslation } from '@/lib/i18n';
import { createCondition, deleteCondition } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyConditionInput,
    STATEMENT_NAME_MAX_LENGTH,
    STATEMENT_NAME_MIN_LENGTH,
    validateLength,
    VALUE_MAX_LENGTH,
} from '../schemas/game-rules';
import type {
    ConditionOperator,
    ConditionType,
    RuleCondition,
    RuleOptions,
} from '../types/game-rules';
import OptionSelect from './option-select';

type ConditionBuilderProps = {
    conditions: RuleCondition[];
    options: RuleOptions;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * Names a reusable logical requirement, three parts at a time.
 *
 *     [Score] [is at least] [20]
 *
 * Three fields in a row, and deliberately nothing more. Section 47 of the module brief rules out arbitrary
 * expressions, and the shape of this form is that decision made visible: there is nowhere to type an
 * operator, nowhere to nest a bracket, and nothing that could be evaluated.
 *
 * The value box disappears for "is true" and "is false", because those operators say everything themselves —
 * and which operators those are is a fact the server sends with the option rather than one this file keeps a
 * copy of.
 *
 * What the form does *not* check is the value against the operator. "Is at least blue" is a finding, not a
 * refusal: somebody halfway through a sentence should not be stopped, and the analysis panel says so where
 * they can act on it.
 */
export default function ConditionBuilder({
    conditions,
    options,
    scope,
    canEdit,
}: ConditionBuilderProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: emptyConditionInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: STATEMENT_NAME_MIN_LENGTH,
                    max: STATEMENT_NAME_MAX_LENGTH,
                    tooShort: t('Say what is being measured.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            value:
                validateLength(input.value, {
                    max: VALUE_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That value is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) => createCondition(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    const operator = options.operators.find(
        (option) => option.value === form.input.operator,
    );

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Conditions')}</CardTitle>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button
                                size="sm"
                                variant="outline"
                                data-test="add-condition"
                            >
                                <Plus className="size-4" />
                                {t('Add condition')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('When is this true?')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Named once and pointed at from wherever it matters — a transition, an outcome, a group.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="condition-name">
                                        {t('What is measured')}
                                    </Label>
                                    <Input
                                        id="condition-name"
                                        value={form.input.name}
                                        dir="auto"
                                        placeholder={t('Score')}
                                        onChange={(event) =>
                                            form.setField(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="condition-type">
                                            {t('Kind')}
                                        </Label>
                                        <OptionSelect
                                            id="condition-type"
                                            value={form.input.condition_type}
                                            options={options.condition_types}
                                            onChange={(value) =>
                                                form.setField(
                                                    'condition_type',
                                                    value as ConditionType,
                                                )
                                            }
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="condition-operator">
                                            {t('Comparison')}
                                        </Label>
                                        <OptionSelect
                                            id="condition-operator"
                                            value={form.input.operator}
                                            options={options.operators}
                                            onChange={(value) =>
                                                form.setField(
                                                    'operator',
                                                    value as ConditionOperator,
                                                )
                                            }
                                        />
                                    </div>

                                    {operator?.expects_value !== false && (
                                        <div className="space-y-2">
                                            <Label htmlFor="condition-value">
                                                {operator?.expects_list
                                                    ? t(
                                                          'Values, comma separated',
                                                      )
                                                    : t('Value')}
                                            </Label>
                                            <Input
                                                id="condition-value"
                                                value={form.input.value}
                                                dir="auto"
                                                onChange={(event) =>
                                                    form.setField(
                                                        'value',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={form.errors.value}
                                            />
                                        </div>
                                    )}
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
                                    {t('Add condition')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </CardHeader>

            <CardContent>
                {conditions.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {t('No conditions named yet.')}
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {conditions.map((condition) => (
                            <li
                                key={condition.id}
                                className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2"
                                data-test={`condition-row-${condition.id}`}
                            >
                                <span className="text-sm" dir="auto">
                                    {condition.statement}
                                </span>

                                <Badge variant="outline">
                                    {condition.condition_type_label}
                                </Badge>

                                {canEdit && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="ms-auto"
                                        aria-label={t('Remove condition')}
                                        onClick={() =>
                                            deleteCondition({
                                                ...scope,
                                                ruleCondition: condition.id,
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
