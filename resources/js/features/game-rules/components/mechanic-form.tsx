import { Plus } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { createMechanic, updateMechanic } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyMechanicInput,
    NAME_MAX_LENGTH,
    NAME_MIN_LENGTH,
    SHORT_DESCRIPTION_MAX_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type {
    MechanicCategory,
    RuleMechanic,
    RuleOptions,
} from '../types/game-rules';
import OptionSelect from './option-select';

type MechanicFormProps = {
    scope: RuleSetScope;
    options: RuleOptions;
    mechanic?: RuleMechanic;
    disabled?: boolean;
    trigger?: React.ReactNode;
};

/**
 * Names a mechanism this rule system uses.
 *
 * The cheapest useful thing a designer can do to a rule set: eight lines that tell any reader what family of
 * game this is before they read a single rule.
 *
 * These are the studio's own words, not entries in the shared design vocabulary GameDesign curates. A
 * designer's "engine of small regrets" belongs here and would never belong in that catalogue, which is why
 * this form is a free text field rather than a picker.
 */
export default function MechanicForm({
    scope,
    options,
    mechanic,
    disabled,
    trigger,
}: MechanicFormProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const editing = mechanic !== undefined;

    const form = useRuleForm({
        initial: editing
            ? {
                  name: mechanic.name,
                  description: mechanic.description ?? '',
                  category: mechanic.category,
              }
            : emptyMechanicInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: NAME_MIN_LENGTH,
                    max: NAME_MAX_LENGTH,
                    tooShort: t('Name the mechanism.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            description:
                validateLength(input.description, {
                    max: SHORT_DESCRIPTION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That description is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            editing
                ? updateMechanic(
                      { ...scope, ruleMechanic: mechanic.id },
                      input,
                      mutation,
                  )
                : createMechanic(scope, input, mutation),
        resetOnSuccess: !editing,
        onSuccess: () => setOpen(false),
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" variant="outline" disabled={disabled}>
                        <Plus className="size-4" />
                        {t('Add mechanic')}
                    </Button>
                )}
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {editing ? t('Edit mechanic') : t('Name a mechanism')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'What kind of system this is. The rules say how it works.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="mechanic-name">{t('Name')}</Label>
                        <Input
                            id="mechanic-name"
                            value={form.input.name}
                            dir="auto"
                            placeholder={t('Worker placement')}
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="mechanic-category">{t('Family')}</Label>
                        <OptionSelect
                            id="mechanic-category"
                            value={form.input.category}
                            options={options.mechanic_categories}
                            onChange={(value) =>
                                form.setField(
                                    'category',
                                    value as MechanicCategory,
                                )
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="mechanic-description">
                            {t('How this game uses it')}
                        </Label>
                        <Textarea
                            id="mechanic-description"
                            rows={3}
                            value={form.input.description}
                            dir="auto"
                            onChange={(event) =>
                                form.setField('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
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

                    <Button onClick={form.submit} disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {editing ? t('Save mechanic') : t('Add mechanic')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
