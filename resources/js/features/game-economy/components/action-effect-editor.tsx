import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { addActionEffect, removeActionEffect } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    EFFECT_TARGET_MAX_LENGTH,
    EFFECT_TARGET_MIN_LENGTH,
    emptyEffectInput,
    validateAmount,
    validateLength,
} from '../schemas/game-economy';
import type {
    ActionEffect,
    ActionEffectType,
    BalanceOptions,
} from '../types/game-economy';

type ActionEffectEditorProps = {
    effects: ActionEffect[];
    options: BalanceOptions;
    scope: ProfileScope;
    economyAction: string;
    canConfigure: boolean;
};

/**
 * What an action does beyond moving resources.
 *
 * The target is a free text field and there is no picker, which is the whole reason effects are separate
 * from costs and rewards: the things an effect acts on are not all records. "Building II" is not a resource,
 * and offering a resource picker here would be the interface telling a designer what their game is allowed
 * to contain.
 *
 * The value field only appears for the kinds of effect that have a magnitude. Which those are comes from the
 * server with each option, so an effect type added later behaves correctly here without this file changing.
 */
export default function ActionEffectEditor({
    effects,
    options,
    scope,
    economyAction,
    canConfigure,
}: ActionEffectEditorProps) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);

    const actionScope = { ...scope, economyAction };

    const selected = options.effect_types.find(
        (type) => type.value === emptyEffectInput.effect_type,
    );

    const form = useBalanceForm({
        initial: emptyEffectInput,
        validate: (input) => ({
            target:
                validateLength(input.target, {
                    min: EFFECT_TARGET_MIN_LENGTH,
                    max: EFFECT_TARGET_MAX_LENGTH,
                    tooShort: t('What does this effect act on?'),
                    tooLong: t('That is too long.'),
                }) ?? undefined,
            value:
                validateAmount(input.value, {
                    missing: '',
                    malformed: t(
                        'Write this as a plain number, such as 5 or 2.5.',
                    ),
                    tooPrecise: t('That is more decimal places than we keep.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            addActionEffect(actionScope, input, mutation),
        onSuccess: () => setAdding(false),
    });

    const expectsValue =
        options.effect_types.find(
            (type) => type.value === form.input.effect_type,
        )?.expects_value ??
        selected?.expects_value ??
        false;

    return (
        <div className="space-y-3">
            {effects.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                    data-test="effects-empty"
                >
                    {t(
                        'No other effects. This is where an unlock, a capacity change or a block goes.',
                    )}
                </p>
            ) : (
                <ul className="divide-y rounded-md border">
                    {effects.map((effect) => (
                        <li
                            key={effect.id}
                            className="flex items-center justify-between gap-3 p-3"
                            data-test={`effect-row-${effect.id}`}
                        >
                            <span className="min-w-0 space-y-1">
                                <span className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium" dir="auto">
                                        {effect.label}
                                    </span>

                                    <Badge variant="outline">
                                        {effect.effect_type_label}
                                    </Badge>
                                </span>

                                {effect.description && (
                                    <span
                                        className="block text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {effect.description}
                                    </span>
                                )}
                            </span>

                            {canConfigure && (
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() =>
                                        removeActionEffect(
                                            actionScope,
                                            effect.id,
                                        )
                                    }
                                    aria-label={t('Remove effect')}
                                    data-test={`delete-effect-${effect.id}`}
                                >
                                    <Trash2 className="size-3" />
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
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
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="effect-type">
                                        {t('Effect type')}
                                    </Label>

                                    <Select
                                        value={form.input.effect_type}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'effect_type',
                                                value as ActionEffectType,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="effect-type"
                                            data-test="effect-type-picker"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {options.effect_types.map(
                                                (type) => (
                                                    <SelectItem
                                                        key={type.value}
                                                        value={type.value}
                                                    >
                                                        {type.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="effect-target">
                                        {t('What does it act on?')}
                                    </Label>

                                    <Input
                                        id="effect-target"
                                        value={form.input.target}
                                        onChange={(event) =>
                                            form.setField(
                                                'target',
                                                event.target.value,
                                            )
                                        }
                                        placeholder={t('Maximum hand size')}
                                        autoComplete="off"
                                        data-test="effect-target-input"
                                    />

                                    <InputError message={form.errors.target} />
                                </div>
                            </div>

                            {expectsValue && (
                                <div className="grid gap-2 sm:max-w-40">
                                    <Label htmlFor="effect-value">
                                        {t('By how much?')}
                                    </Label>

                                    <Input
                                        id="effect-value"
                                        value={form.input.value}
                                        onChange={(event) =>
                                            form.setField(
                                                'value',
                                                event.target.value,
                                            )
                                        }
                                        inputMode="decimal"
                                        dir="ltr"
                                        placeholder="2"
                                        autoComplete="off"
                                        data-test="effect-value-input"
                                    />

                                    <InputError message={form.errors.value} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="effect-description">
                                    {t('Description')}{' '}
                                    <span className="font-normal text-muted-foreground">
                                        {t('(optional)')}
                                    </span>
                                </Label>

                                <Input
                                    id="effect-description"
                                    value={form.input.description}
                                    onChange={(event) =>
                                        form.setField(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    autoComplete="off"
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={form.processing}
                                    data-test="submit-effect-button"
                                >
                                    {form.processing && <Spinner />}
                                    {t('Add effect')}
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
                            data-test="add-effect-button"
                        >
                            <Plus className="size-4" />
                            {t('Add effect')}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
