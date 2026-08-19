import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    addActionCost,
    addActionReward,
    removeActionCost,
    removeActionReward,
} from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import { emptyActionLineInput, validateAmount } from '../schemas/game-economy';
import type { ActionLine, ResourceType } from '../types/game-economy';
import Amount from './amount';

type ActionLineEditorProps = {
    kind: 'cost' | 'reward';
    lines: ActionLine[];
    resources: ResourceType[];
    scope: ProfileScope;
    economyAction: string;
    canConfigure: boolean;
};

/**
 * What an action takes, or what it gives back.
 *
 * One editor for both, because the *input* is identical: a resource, an amount, and optionally a range. The
 * two are rendered as separate panels and written by separate commands, because that is where they actually
 * differ — but a designer filling either one is doing the same three things, and two editors would be two
 * places for the variable-range control to be forgotten.
 *
 * The resource is chosen once, when the line is added, and cannot be changed afterwards. Changing which
 * resource a line is about is not an edit to the price — it is removing one and adding another — and the
 * server refuses it for the same reason.
 *
 * A resource the action already names is left out of the picker, because an action costing "2 wood and 3
 * more wood" is a data entry mistake rather than a design.
 */
export default function ActionLineEditor({
    kind,
    lines,
    resources,
    scope,
    economyAction,
    canConfigure,
}: ActionLineEditorProps) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);

    const taken = new Set(lines.map((line) => line.resource_type_id));
    const available = resources.filter((resource) => !taken.has(resource.id));

    const actionScope = { ...scope, economyAction };

    const form = useBalanceForm({
        initial: {
            ...emptyActionLineInput,
            resource_type_id: available[0]?.id ?? '',
        },
        validate: (input) => ({
            resource_type_id:
                input.resource_type_id === ''
                    ? t('Choose a resource.')
                    : undefined,
            amount:
                validateAmount(input.amount, {
                    required: true,
                    allowNegative: false,
                    missing: t('How much?'),
                    malformed: t(
                        'Write this as a plain number, such as 5 or 2.5.',
                    ),
                    negative: t('Write the amount as a positive number.'),
                    tooPrecise: t('That is more decimal places than we keep.'),
                }) ?? undefined,
            min_amount: rangeError(input.min_amount) ?? undefined,
            max_amount: rangeError(input.max_amount) ?? undefined,
        }),
        perform: (input, mutation) =>
            kind === 'cost'
                ? addActionCost(actionScope, input, mutation)
                : addActionReward(actionScope, input, mutation),
        onSuccess: () => setAdding(false),
    });

    function rangeError(value: string): string | null {
        return validateAmount(value, {
            allowNegative: false,
            missing: '',
            malformed: t('Write this as a plain number, such as 5 or 2.5.'),
            negative: t('Write the amount as a positive number.'),
            tooPrecise: t('That is more decimal places than we keep.'),
        });
    }

    const remove = (line: ActionLine) =>
        kind === 'cost'
            ? removeActionCost(actionScope, line.id)
            : removeActionReward(actionScope, line.id);

    return (
        <div className="space-y-3">
            {lines.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                    data-test={`${kind}s-empty`}
                >
                    {kind === 'cost'
                        ? t('This action is free.')
                        : t('This action pays nothing.')}
                </p>
            ) : (
                <ul className="divide-y rounded-md border">
                    {lines.map((line) => (
                        <li
                            key={line.id}
                            className="flex items-center justify-between gap-3 p-3"
                            data-test={`${kind}-row-${line.id}`}
                        >
                            <span className="min-w-0">
                                <span className="block" dir="auto">
                                    {line.resource_name}
                                </span>

                                {line.is_variable && (
                                    <span className="block text-xs text-muted-foreground">
                                        {line.min_amount !== null ||
                                        line.max_amount !== null
                                            ? t('Varies from :min to :max', {
                                                  min: line.min_amount ?? '?',
                                                  max: line.max_amount ?? '?',
                                              })
                                            : t('Varies, range not given')}
                                    </span>
                                )}
                            </span>

                            <span className="flex items-center gap-3">
                                <Amount
                                    value={line.amount}
                                    unit={line.unit}
                                    className="font-medium"
                                />

                                {canConfigure && (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => remove(line)}
                                        aria-label={
                                            kind === 'cost'
                                                ? t('Remove cost')
                                                : t('Remove reward')
                                        }
                                        data-test={`delete-${kind}-${line.id}`}
                                    >
                                        <Trash2 className="size-3" />
                                    </Button>
                                )}
                            </span>
                        </li>
                    ))}
                </ul>
            )}

            {canConfigure && available.length > 0 && (
                <>
                    {adding ? (
                        <form
                            className="grid gap-3 rounded-md border p-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.submit();
                            }}
                        >
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor={`${kind}-resource`}>
                                        {t('Resource')}
                                    </Label>

                                    <Select
                                        value={form.input.resource_type_id}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'resource_type_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id={`${kind}-resource`}
                                            data-test={`${kind}-resource-picker`}
                                        >
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {available.map((resource) => (
                                                <SelectItem
                                                    key={resource.id}
                                                    value={resource.id}
                                                >
                                                    {resource.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    <InputError
                                        message={form.errors.resource_type_id}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`${kind}-amount`}>
                                        {t('Amount')}
                                    </Label>

                                    <Input
                                        id={`${kind}-amount`}
                                        value={form.input.amount}
                                        onChange={(event) =>
                                            form.setField(
                                                'amount',
                                                event.target.value,
                                            )
                                        }
                                        inputMode="decimal"
                                        dir="ltr"
                                        placeholder="5"
                                        autoComplete="off"
                                        data-test={`${kind}-amount-input`}
                                    />

                                    <InputError message={form.errors.amount} />
                                </div>
                            </div>

                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.input.is_variable}
                                    onCheckedChange={(checked) =>
                                        form.setField(
                                            'is_variable',
                                            checked === true,
                                        )
                                    }
                                    data-test={`${kind}-is-variable`}
                                />
                                {t('This amount varies')}
                            </label>

                            {form.input.is_variable && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor={`${kind}-min`}>
                                            {t('Minimum')}
                                        </Label>

                                        <Input
                                            id={`${kind}-min`}
                                            value={form.input.min_amount}
                                            onChange={(event) =>
                                                form.setField(
                                                    'min_amount',
                                                    event.target.value,
                                                )
                                            }
                                            inputMode="decimal"
                                            dir="ltr"
                                            autoComplete="off"
                                        />

                                        <InputError
                                            message={form.errors.min_amount}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor={`${kind}-max`}>
                                            {t('Maximum')}
                                        </Label>

                                        <Input
                                            id={`${kind}-max`}
                                            value={form.input.max_amount}
                                            onChange={(event) =>
                                                form.setField(
                                                    'max_amount',
                                                    event.target.value,
                                                )
                                            }
                                            inputMode="decimal"
                                            dir="ltr"
                                            autoComplete="off"
                                        />

                                        <InputError
                                            message={form.errors.max_amount}
                                        />
                                    </div>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={form.processing}
                                    data-test={`submit-${kind}-button`}
                                >
                                    {form.processing && <Spinner />}
                                    {kind === 'cost'
                                        ? t('Add cost')
                                        : t('Add reward')}
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
                            data-test={`add-${kind}-button`}
                        >
                            <Plus className="size-4" />
                            {kind === 'cost' ? t('Add cost') : t('Add reward')}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
