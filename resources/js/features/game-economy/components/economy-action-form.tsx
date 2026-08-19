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
import { createEconomyAction, updateEconomyAction } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    ACTION_NAME_MAX_LENGTH,
    ACTION_NAME_MIN_LENGTH,
    DESCRIPTION_MAX_LENGTH,
    emptyActionInput,
    validateLength,
} from '../schemas/game-economy';
import type { EconomyAction } from '../types/game-economy';

type EconomyActionFormProps = {
    scope: ProfileScope;
    action?: EconomyAction;
    trigger?: React.ReactNode;
};

/**
 * Declares an action, or renames one.
 *
 * Two fields, and that is the whole form. What the action *costs* and *pays* is edited on its own page,
 * because that is how designers work: "we need a Build action" comes before anybody has decided what it
 * takes, and a create form that demanded a resource list would also demand that the resources already
 * existed — which is the wrong way round for a new configuration.
 *
 * The analysis will report the empty action as costing nothing and doing nothing, which is true and is
 * exactly the reminder somebody wants.
 */
export default function EconomyActionForm({
    scope,
    action,
    trigger,
}: EconomyActionFormProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const editing = action !== undefined;

    const form = useBalanceForm({
        initial: editing
            ? { name: action.name, description: action.description ?? '' }
            : emptyActionInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: ACTION_NAME_MIN_LENGTH,
                    max: ACTION_NAME_MAX_LENGTH,
                    tooShort: t('Give the action a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            description:
                validateLength(input.description, {
                    max: DESCRIPTION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That description is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            editing
                ? updateEconomyAction(
                      { ...scope, economyAction: action.id },
                      input,
                      mutation,
                  )
                : createEconomyAction(scope, input, mutation),
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
                {trigger ?? (
                    <Button size="sm" data-test="add-action-button">
                        <Plus className="size-4" />
                        {t('Add action')}
                    </Button>
                )}
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {editing ? t('Edit action') : t('Add an action')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'An action is something that moves the economy: build, harvest, trade, place a worker. You price it once it exists.',
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
                        <Label htmlFor="action-name">{t('Name')}</Label>

                        <Input
                            id="action-name"
                            value={form.input.name}
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                            placeholder={t('Build')}
                            autoComplete="off"
                            data-test="action-name-input"
                        />

                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="action-description">
                            {t('Description')}{' '}
                            <span className="font-normal text-muted-foreground">
                                {t('(optional)')}
                            </span>
                        </Label>

                        <Textarea
                            id="action-description"
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
                            data-test="submit-action-button"
                        >
                            {form.processing && <Spinner />}
                            {editing ? t('Save changes') : t('Add action')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
