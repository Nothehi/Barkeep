import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { createEffect, deleteEffect } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    EFFECT_TARGET_MAX_LENGTH,
    EFFECT_TARGET_MIN_LENGTH,
    emptyEffectInput,
    validateLength,
    VALUE_MAX_LENGTH,
} from '../schemas/game-rules';
import type {
    EconomyChoices,
    EffectType,
    RuleEffect,
    RuleOptions,
} from '../types/game-rules';
import OptionSelect from './option-select';

type EffectEditorProps = {
    effects: RuleEffect[];
    options: RuleOptions;
    economy: EconomyChoices;
    scope: RuleSetScope;
    canEdit: boolean;
    owner: { ruleId: string } | { actionId: string };
};

/**
 * What happens when a rule or an action resolves.
 *
 *     RESOURCE  ·  Victory points  ·  +3
 *
 * Three structured fields, and nothing executable. "+3" is a string here and stays one all the way to the
 * database, which is what lets a rulebook say "half, rounded down" — and what makes it obvious that nothing
 * is going to add it up.
 *
 * The value box only appears for the types that imply an amount, and which those are is a fact the server
 * sends with the option. Gaining wood without saying how much is not an effect anybody can play with;
 * unlocking an ability is complete as it stands.
 */
export default function EffectEditor({
    effects,
    options,
    economy,
    scope,
    canEdit,
    owner,
}: EffectEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: {
            ...emptyEffectInput,
            rule_id: 'ruleId' in owner ? owner.ruleId : '',
            action_id: 'actionId' in owner ? owner.actionId : '',
        },
        validate: (input) => ({
            target:
                validateLength(input.target, {
                    min: EFFECT_TARGET_MIN_LENGTH,
                    max: EFFECT_TARGET_MAX_LENGTH,
                    tooShort: t('Say what this acts on.'),
                    tooLong: t('That is too long.'),
                }) ?? undefined,
            value:
                validateLength(input.value, {
                    max: VALUE_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That value is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) => createEffect(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    const effectType = options.effect_types.find(
        (option) => option.value === form.input.effect_type,
    );

    return (
        <section className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <h3 className="text-sm font-medium">{t('Effects')}</h3>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button
                                size="sm"
                                variant="outline"
                                data-test="add-effect"
                            >
                                <Plus className="size-4" />
                                {t('Add effect')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('What happens?')}</DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Recorded, not run. Nothing here is carried out by the app.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="effect-type">
                                        {t('Kind of effect')}
                                    </Label>
                                    <OptionSelect
                                        id="effect-type"
                                        value={form.input.effect_type}
                                        options={options.effect_types}
                                        onChange={(value) =>
                                            form.setField(
                                                'effect_type',
                                                value as EffectType,
                                            )
                                        }
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {effectType?.description ?? ''}
                                    </p>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="effect-target">
                                            {t('Acts on')}
                                        </Label>
                                        <Input
                                            id="effect-target"
                                            value={form.input.target}
                                            dir="auto"
                                            placeholder={t('Victory points')}
                                            onChange={(event) =>
                                                form.setField(
                                                    'target',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={form.errors.target}
                                        />
                                    </div>

                                    {effectType?.expects_value !== false && (
                                        <div className="space-y-2">
                                            <Label htmlFor="effect-value">
                                                {t('How much')}
                                            </Label>
                                            <Input
                                                id="effect-value"
                                                value={form.input.value}
                                                dir="auto"
                                                placeholder="+3"
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

                                {effectType?.is_economic &&
                                    economy.available && (
                                        <div className="space-y-2">
                                            <Label htmlFor="effect-resource">
                                                {t('Resource in the economy')}
                                            </Label>
                                            <OptionSelect
                                                id="effect-resource"
                                                value={
                                                    form.input
                                                        .economy_resource_slug
                                                }
                                                options={economy.resources.map(
                                                    (choice) => ({
                                                        value: choice.handle,
                                                        label: choice.label,
                                                    }),
                                                )}
                                                emptyLabel={t('None')}
                                                onChange={(value) =>
                                                    form.setField(
                                                        'economy_resource_slug',
                                                        value,
                                                    )
                                                }
                                            />
                                        </div>
                                    )}
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
                                    {t('Add effect')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </div>

            {effects.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('Nothing happens yet.')}
                </p>
            ) : (
                <ul className="space-y-2">
                    {effects.map((effect) => (
                        <li
                            key={effect.id}
                            className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2"
                        >
                            <Badge variant="outline">
                                {effect.effect_type_label}
                            </Badge>

                            <span className="text-sm" dir="auto">
                                {effect.target}
                            </span>

                            {effect.value && (
                                <Badge variant="secondary" dir="auto">
                                    {effect.value}
                                </Badge>
                            )}

                            {effect.economy_resource_slug && (
                                <Badge variant="secondary" dir="ltr">
                                    {effect.economy_resource_slug}
                                </Badge>
                            )}

                            {canEdit && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="ms-auto"
                                    aria-label={t('Remove effect')}
                                    onClick={() =>
                                        deleteEffect({
                                            ...scope,
                                            ruleEffect: effect.id,
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
        </section>
    );
}
