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
import { createRuleSet } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleScope } from '../hooks/use-rule-scope';
import {
    DESCRIPTION_MAX_LENGTH,
    emptyRuleSetInput,
    RULE_SET_NAME_MAX_LENGTH,
    RULE_SET_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-rules';

type CreateRuleSetDialogProps = {
    scope: RuleScope;
    disabled?: boolean;
};

/**
 * Starts writing a game's rules down.
 *
 * Two fields, and that is the whole form. A rule set is created empty and then written — a create form that
 * asked for a first phase or a victory condition would be asking a designer to have finished before they had
 * started.
 *
 * The validator will report the empty set as having no rules, no phases and no way to win, which is true and
 * is exactly the checklist somebody needs.
 */
export default function CreateRuleSetDialog({
    scope,
    disabled,
}: CreateRuleSetDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: emptyRuleSetInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: RULE_SET_NAME_MIN_LENGTH,
                    max: RULE_SET_NAME_MAX_LENGTH,
                    tooShort: t('Give this rule set a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            description:
                validateLength(input.description, {
                    max: DESCRIPTION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That description is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) => createRuleSet(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button disabled={disabled} data-test="create-rule-set">
                    <Plus className="size-4" />
                    {t('New rule set')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Write the rules down')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'A rule set belongs to this version of the design, so the rules a playtest was run under stay readable later.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="rule-set-name">{t('Name')}</Label>
                        <Input
                            id="rule-set-name"
                            value={form.input.name}
                            dir="auto"
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="rule-set-description">
                            {t('Description')}
                        </Label>
                        <Textarea
                            id="rule-set-description"
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
                        {t('Create rule set')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
