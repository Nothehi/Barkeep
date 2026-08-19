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
import { createBalanceProfile } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { BalanceScope } from '../hooks/use-balance-scope';
import {
    DESCRIPTION_MAX_LENGTH,
    emptyProfileInput,
    PROFILE_NAME_MAX_LENGTH,
    PROFILE_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-economy';

type CreateBalanceProfileDialogProps = {
    scope: BalanceScope;
    versionLabel: string;
};

/**
 * Starts a balance configuration for one design state.
 *
 * There is no version picker, because the version is the page you are already on. A profile belongs to a
 * design state rather than to a game — wood income was 2 in v1 and 3 in v2 — and choosing which state from
 * inside the state's own screen would be an invitation to pick the wrong one.
 *
 * The profile is created empty rather than seeded with a starter set of resources. A platform that created
 * "Gold, Wood, Stone" for every new configuration would be telling every studio what kind of game they are
 * making.
 */
export default function CreateBalanceProfileDialog({
    scope,
    versionLabel,
}: CreateBalanceProfileDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useBalanceForm({
        initial: emptyProfileInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: PROFILE_NAME_MIN_LENGTH,
                    max: PROFILE_NAME_MAX_LENGTH,
                    tooShort: t('Give the profile a name.'),
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
            createBalanceProfile(scope, input, {
                ...mutation,
                preserveScroll: false,
            }),
        resetOnSuccess: false,
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
                <Button data-test="create-profile-button">
                    <Plus className="size-4" />
                    {t('New balance profile')}
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{t('Start a balance profile')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'The numbers belong to one version of the design, so this profile describes the economy as of :version.',
                            { version: versionLabel },
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
                        <Label htmlFor="profile-name">{t('Name')}</Label>

                        <Input
                            id="profile-name"
                            value={form.input.name}
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                            placeholder={t('First pass')}
                            autoComplete="off"
                            data-test="profile-name-input"
                        />

                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="profile-description">
                            {t('Description')}{' '}
                            <span className="font-normal text-muted-foreground">
                                {t('(optional)')}
                            </span>
                        </Label>

                        <Textarea
                            id="profile-description"
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
                            data-test="submit-profile-button"
                        >
                            {form.processing && <Spinner />}
                            {t('Start profile')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
