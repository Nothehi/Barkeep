import { Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { createResource, updateResource } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    DESCRIPTION_MAX_LENGTH,
    emptyResourceInput,
    RESOURCE_NAME_MAX_LENGTH,
    RESOURCE_NAME_MIN_LENGTH,
    UNIT_MAX_LENGTH,
    validateAmount,
    validateLength,
} from '../schemas/game-economy';
import type {
    BalanceOptions,
    ResourceCategory,
    ResourceType,
} from '../types/game-economy';

type ResourceFormProps = {
    scope: ProfileScope;
    options: BalanceOptions;
    /**
     * The resource being edited, or nothing when declaring a new one.
     */
    resource?: ResourceType;
    trigger?: React.ReactNode;
};

/**
 * Declares a resource, or retunes one.
 *
 * One form for both, because they ask for exactly the same things — and because the fields that matter here
 * are the four flags, which a designer sets once and then rarely thinks about again. Splitting the form
 * would mean the create dialog and the edit dialog drifting on which flags they offer.
 *
 * The flags are what separate gold from action points, and the category is not: the category is filing, and
 * nothing in the analysis reads it. The defaults describe the ordinary case — a material you gather, hold
 * and spend — so most resources are one field and a button.
 *
 * The bounds are left empty rather than zeroed. Empty means unbounded, which is a different statement from
 * "capped at zero", and a form that filled them in would invent a limit nobody set.
 */
export default function ResourceForm({
    scope,
    options,
    resource,
    trigger,
}: ResourceFormProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const editing = resource !== undefined;

    const form = useBalanceForm({
        initial: editing
            ? {
                  name: resource.name,
                  description: resource.description ?? '',
                  unit: resource.unit ?? '',
                  category: resource.category,
                  is_tradeable: resource.is_tradeable,
                  is_accumulative: resource.is_accumulative,
                  is_spendable: resource.is_spendable,
                  is_convertible: resource.is_convertible,
                  min_value: resource.min_value ?? '',
                  max_value: resource.max_value ?? '',
                  starting_value: resource.starting_value ?? '',
              }
            : emptyResourceInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: RESOURCE_NAME_MIN_LENGTH,
                    max: RESOURCE_NAME_MAX_LENGTH,
                    tooShort: t('Give the resource a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            unit:
                validateLength(input.unit, {
                    max: UNIT_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That unit is too long.'),
                }) ?? undefined,
            description:
                validateLength(input.description, {
                    max: DESCRIPTION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That description is too long.'),
                }) ?? undefined,
            min_value: amountError(input.min_value) ?? undefined,
            max_value: amountError(input.max_value) ?? undefined,
            starting_value: amountError(input.starting_value) ?? undefined,
        }),
        perform: (input, mutation) =>
            editing
                ? updateResource(scope, resource.id, input, mutation)
                : createResource(scope, input, mutation),
        resetOnSuccess: false,
        onSuccess: () => setOpen(false),
    });

    function amountError(value: string): string | null {
        return validateAmount(value, {
            missing: '',
            malformed: t('Write this as a plain number, such as 5 or 2.5.'),
            tooPrecise: t('That is more decimal places than we keep.'),
        });
    }

    const close = (next: boolean) => {
        setOpen(next);

        if (!next) {
            form.reset();
        }
    };

    const flags: {
        field:
            | 'is_tradeable'
            | 'is_accumulative'
            | 'is_spendable'
            | 'is_convertible';
        label: string;
        hint: string;
    }[] = [
        {
            field: 'is_spendable',
            label: t('Spendable'),
            hint: t('Players give it up to do things.'),
        },
        {
            field: 'is_accumulative',
            label: t('Accumulative'),
            hint: t('It carries over rather than resetting.'),
        },
        {
            field: 'is_tradeable',
            label: t('Tradeable'),
            hint: t('Players can pass it between themselves.'),
        },
        {
            field: 'is_convertible',
            label: t('Convertible'),
            hint: t('It can be exchanged for another resource.'),
        },
    ];

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" data-test="add-resource-button">
                        <Plus className="size-4" />
                        {t('Add resource')}
                    </Button>
                )}
            </DialogTrigger>

            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {editing ? t('Edit resource') : t('Add a resource')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'A resource is something players hold, gain and spend. What it can do is set by the switches below, not by its category.',
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
                    <div className="grid gap-2 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="resource-name">{t('Name')}</Label>

                            <Input
                                id="resource-name"
                                value={form.input.name}
                                onChange={(event) =>
                                    form.setField('name', event.target.value)
                                }
                                placeholder={t('Wood')}
                                autoComplete="off"
                                data-test="resource-name-input"
                            />

                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="resource-unit">
                                {t('Unit')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Input
                                id="resource-unit"
                                value={form.input.unit}
                                onChange={(event) =>
                                    form.setField('unit', event.target.value)
                                }
                                placeholder={t('cubes')}
                                autoComplete="off"
                            />

                            <InputError message={form.errors.unit} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="resource-category">
                            {t('Category')}
                        </Label>

                        <Select
                            value={form.input.category}
                            onValueChange={(value) =>
                                form.setField(
                                    'category',
                                    value as ResourceCategory,
                                )
                            }
                        >
                            <SelectTrigger
                                id="resource-category"
                                data-test="resource-category-picker"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                {options.resource_categories.map((category) => (
                                    <SelectItem
                                        key={category.value}
                                        value={category.value}
                                    >
                                        {category.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <p className="text-xs text-muted-foreground">
                            {
                                options.resource_categories.find(
                                    (category) =>
                                        category.value === form.input.category,
                                )?.description
                            }
                        </p>
                    </div>

                    <fieldset className="grid gap-3 rounded-md border p-3">
                        <legend className="px-1 text-sm font-medium">
                            {t('What can players do with it?')}
                        </legend>

                        {flags.map((flag) => (
                            <label
                                key={flag.field}
                                className="flex items-start gap-3 text-sm"
                            >
                                <Checkbox
                                    checked={form.input[flag.field]}
                                    onCheckedChange={(checked) =>
                                        form.setField(
                                            flag.field,
                                            checked === true,
                                        )
                                    }
                                    data-test={`resource-${flag.field}`}
                                />

                                <span>
                                    <span className="font-medium">
                                        {flag.label}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {flag.hint}
                                    </span>
                                </span>
                            </label>
                        ))}
                    </fieldset>

                    <div className="grid gap-2 sm:grid-cols-3">
                        {(
                            [
                                ['starting_value', t('Starting value')],
                                ['min_value', t('Minimum')],
                                ['max_value', t('Maximum')],
                            ] as const
                        ).map(([field, label]) => (
                            <div className="grid gap-2" key={field}>
                                <Label htmlFor={`resource-${field}`}>
                                    {label}
                                </Label>

                                <Input
                                    id={`resource-${field}`}
                                    value={form.input[field]}
                                    onChange={(event) =>
                                        form.setField(field, event.target.value)
                                    }
                                    inputMode="decimal"
                                    dir="ltr"
                                    placeholder={t('unlimited')}
                                    autoComplete="off"
                                    data-test={`resource-${field}-input`}
                                />

                                <InputError message={form.errors[field]} />
                            </div>
                        ))}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="resource-description">
                            {t('Description')}{' '}
                            <span className="font-normal text-muted-foreground">
                                {t('(optional)')}
                            </span>
                        </Label>

                        <Textarea
                            id="resource-description"
                            value={form.input.description}
                            onChange={(event) =>
                                form.setField('description', event.target.value)
                            }
                            rows={2}
                        />

                        <InputError message={form.errors.description} />
                    </div>

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
                            data-test="submit-resource-button"
                        >
                            {form.processing && <Spinner />}
                            {editing ? t('Save changes') : t('Add resource')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
