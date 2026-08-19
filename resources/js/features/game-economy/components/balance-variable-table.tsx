import { AlertTriangle, Check, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import {
    createBalanceVariable,
    deleteBalanceVariable,
    updateBalanceVariable,
} from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    emptyVariableInput,
    isValidAmount,
    validateAmount,
    validateLength,
    VARIABLE_NAME_MAX_LENGTH,
    VARIABLE_NAME_MIN_LENGTH,
} from '../schemas/game-economy';
import type {
    BalanceOptions,
    BalanceVariable,
    BalanceVariableCategory,
} from '../types/game-economy';
import Amount from './amount';

type BalanceVariableTableProps = {
    variables: BalanceVariable[];
    scope: ProfileScope;
    options: BalanceOptions;
    canConfigure: boolean;
};

/**
 * The numbers a designer tunes, grouped by what they do.
 *
 * The grouping is the reason this is a table rather than a list of cards. Twenty-seven numbers in one flat
 * column is the spreadsheet this module exists to replace; twenty-seven numbers under nine headings is
 * something a designer can find their way around.
 *
 * Editing is inline and one cell at a time, because that is how tuning happens — somebody changes starting
 * gold from 10 to 12, plays, and changes it back. Each save is a PATCH carrying only `value`, so the unit,
 * the range and the category around it are untouched.
 *
 * A value outside its own range is marked rather than refused, here and on the server. A designer narrowing
 * a range around a number they are about to change would otherwise be stopped by their own guardrail.
 */
export default function BalanceVariableTable({
    variables,
    scope,
    options,
    canConfigure,
}: BalanceVariableTableProps) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);

    const grouped = options.variable_categories
        .map((category) => ({
            category,
            entries: variables.filter(
                (variable) => variable.category === category.value,
            ),
        }))
        .filter((group) => group.entries.length > 0);

    const form = useBalanceForm({
        initial: emptyVariableInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: VARIABLE_NAME_MIN_LENGTH,
                    max: VARIABLE_NAME_MAX_LENGTH,
                    tooShort: t('Give the variable a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            value:
                validateAmount(input.value, {
                    required: true,
                    missing: t('What is it set to?'),
                    malformed: t(
                        'Write this as a plain number, such as 5 or 2.5.',
                    ),
                    tooPrecise: t('That is more decimal places than we keep.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createBalanceVariable(scope, input, mutation),
        onSuccess: () => setAdding(false),
    });

    return (
        <div className="space-y-4">
            {variables.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                    data-test="variables-empty"
                >
                    {t(
                        'No variables yet. These are the numbers you change between playtests: starting gold, production per round, the victory threshold.',
                    )}
                </p>
            ) : (
                grouped.map((group) => (
                    <section key={group.category.value} className="space-y-2">
                        <h3 className="text-sm font-medium text-muted-foreground">
                            {group.category.label}
                        </h3>

                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                                    <tr>
                                        <th className="p-2 text-start font-medium">
                                            {t('Variable')}
                                        </th>
                                        <th className="p-2 text-start font-medium">
                                            {t('Value')}
                                        </th>
                                        <th className="p-2 text-start font-medium">
                                            {t('Unit')}
                                        </th>
                                        <th className="p-2 text-start font-medium">
                                            {t('Range')}
                                        </th>
                                        <th className="p-2 text-end font-medium">
                                            <span className="sr-only">
                                                {t('Actions')}
                                            </span>
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y">
                                    {group.entries.map((variable) => (
                                        <VariableRow
                                            key={variable.id}
                                            variable={variable}
                                            scope={scope}
                                            canConfigure={canConfigure}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                ))
            )}

            {canConfigure && (
                <>
                    {adding ? (
                        <form
                            className="grid gap-3 rounded-md border p-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.submit();
                            }}
                        >
                            <div className="grid gap-3 sm:grid-cols-4">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="variable-name">
                                        {t('Name')}
                                    </Label>

                                    <Input
                                        id="variable-name"
                                        value={form.input.name}
                                        onChange={(event) =>
                                            form.setField(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                        placeholder={t('Starting gold')}
                                        autoComplete="off"
                                        data-test="variable-name-input"
                                    />

                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="variable-value">
                                        {t('Value')}
                                    </Label>

                                    <Input
                                        id="variable-value"
                                        value={form.input.value}
                                        onChange={(event) =>
                                            form.setField(
                                                'value',
                                                event.target.value,
                                            )
                                        }
                                        inputMode="decimal"
                                        dir="ltr"
                                        placeholder="10"
                                        autoComplete="off"
                                        data-test="variable-value-input"
                                    />

                                    <InputError message={form.errors.value} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="variable-unit">
                                        {t('Unit')}
                                    </Label>

                                    <Input
                                        id="variable-unit"
                                        value={form.input.unit}
                                        onChange={(event) =>
                                            form.setField(
                                                'unit',
                                                event.target.value,
                                            )
                                        }
                                        placeholder={t('gold')}
                                        autoComplete="off"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="variable-min">
                                        {t('Minimum')}
                                    </Label>

                                    <Input
                                        id="variable-min"
                                        value={form.input.min_value}
                                        onChange={(event) =>
                                            form.setField(
                                                'min_value',
                                                event.target.value,
                                            )
                                        }
                                        inputMode="decimal"
                                        dir="ltr"
                                        autoComplete="off"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="variable-max">
                                        {t('Maximum')}
                                    </Label>

                                    <Input
                                        id="variable-max"
                                        value={form.input.max_value}
                                        onChange={(event) =>
                                            form.setField(
                                                'max_value',
                                                event.target.value,
                                            )
                                        }
                                        inputMode="decimal"
                                        dir="ltr"
                                        autoComplete="off"
                                    />
                                </div>

                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="variable-category">
                                        {t('Category')}
                                    </Label>

                                    <Select
                                        value={form.input.category}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'category',
                                                value as BalanceVariableCategory,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="variable-category"
                                            data-test="variable-category-picker"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {options.variable_categories.map(
                                                (category) => (
                                                    <SelectItem
                                                        key={category.value}
                                                        value={category.value}
                                                    >
                                                        {category.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={form.processing}
                                    data-test="submit-variable-button"
                                >
                                    {form.processing && <Spinner />}
                                    {t('Add variable')}
                                </Button>

                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => {
                                        setAdding(false);
                                        form.reset();
                                    }}
                                >
                                    {t('Cancel')}
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setAdding(true)}
                            data-test="add-variable-button"
                        >
                            <Plus className="size-4" />
                            {t('Add variable')}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}

/**
 * One row of the variable table, with its value editable in place.
 *
 * The edit is deliberately narrow: it sends `value` and nothing else. A row that submitted its whole shape
 * would overwrite the unit and the range with whatever the row happened to be holding, which is how an
 * inline editor quietly loses fields it does not display.
 */
function VariableRow({
    variable,
    scope,
    canConfigure,
}: {
    variable: BalanceVariable;
    scope: ProfileScope;
    canConfigure: boolean;
}) {
    const { t } = useTranslation();
    const [editing, setEditing] = useState(false);
    const [value, setValue] = useState(variable.value);
    const [saving, setSaving] = useState(false);

    const save = () => {
        if (!isValidAmount(value)) {
            return;
        }

        setSaving(true);

        updateBalanceVariable(
            scope,
            variable.id,
            { value },
            {
                onSuccess: () => setEditing(false),
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <tr data-test={`variable-row-${variable.slug}`}>
            <td className="p-2">
                <span className="block" dir="auto">
                    {variable.name}
                </span>

                {(variable.resource_name || variable.action_name) && (
                    <span className="block text-xs text-muted-foreground">
                        <span dir="auto">
                            {[variable.resource_name, variable.action_name]
                                .filter(Boolean)
                                .join(' · ')}
                        </span>
                    </span>
                )}
            </td>

            <td className="p-2">
                {editing ? (
                    <span className="flex items-center gap-1">
                        <Input
                            value={value}
                            onChange={(event) => setValue(event.target.value)}
                            inputMode="decimal"
                            dir="ltr"
                            className="h-8 w-24"
                            aria-label={t('Value')}
                            data-test={`variable-input-${variable.slug}`}
                        />

                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={save}
                            disabled={saving || !isValidAmount(value)}
                            aria-label={t('Save')}
                            data-test={`variable-save-${variable.slug}`}
                        >
                            {saving ? (
                                <Spinner />
                            ) : (
                                <Check className="size-3" />
                            )}
                        </Button>

                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => {
                                setValue(variable.value);
                                setEditing(false);
                            }}
                            aria-label={t('Cancel')}
                        >
                            <X className="size-3" />
                        </Button>
                    </span>
                ) : (
                    <span className="flex items-center gap-2">
                        <Amount value={variable.value} />

                        {!variable.is_within_range && (
                            <AlertTriangle
                                className="size-3 text-destructive"
                                aria-label={t('Outside its range')}
                            />
                        )}
                    </span>
                )}
            </td>

            <td className="p-2 text-muted-foreground" dir="auto">
                {variable.unit ?? '—'}
            </td>

            <td className="p-2 text-muted-foreground" dir="ltr">
                {variable.min_value === null && variable.max_value === null
                    ? '—'
                    : `${variable.min_value ?? '−∞'} … ${variable.max_value ?? '∞'}`}
            </td>

            <td className="p-2 text-end">
                {canConfigure && !editing && (
                    <span className="inline-flex gap-1">
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => setEditing(true)}
                            aria-label={t('Edit value')}
                            data-test={`variable-edit-${variable.slug}`}
                        >
                            <Pencil className="size-3" />
                        </Button>

                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() =>
                                deleteBalanceVariable(scope, variable.id)
                            }
                            aria-label={t('Remove variable')}
                            data-test={`variable-delete-${variable.slug}`}
                        >
                            <Trash2 className="size-3" />
                        </Button>
                    </span>
                )}
            </td>
        </tr>
    );
}
