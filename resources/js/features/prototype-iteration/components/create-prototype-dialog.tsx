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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { GameVersion } from '@/features/games';
import { useTranslation } from '@/lib/i18n';
import { createPrototype } from '../api';
import { useDesignForm } from '../hooks/use-design-form';
import {
    emptyCreatePrototypeInput,
    PROTOTYPE_NAME_MAX_LENGTH,
    PROTOTYPE_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/prototype-iteration';
import type {
    PrototypeOptions,
    PrototypeType,
} from '../types/prototype-iteration';

type CreatePrototypeDialogProps = {
    workspace: string;
    game: string;
    versions: GameVersion[];
    options: PrototypeOptions;
};

/**
 * Starts a prototype from a version of the game's design.
 *
 * The design version picker is the field that makes this more than a name. A prototype records which state of
 * the design it was built to implement, and that pairing is what lets somebody a year later tell "the combat
 * prototype" from the one built before the economy was reworked. It defaults to the newest version, which is
 * what a designer means by "the current design".
 *
 * There is no version field when the game has none, because there is nothing to build from yet — the dialog
 * says so rather than offering an empty picker.
 */
export default function CreatePrototypeDialog({
    workspace,
    game,
    versions,
    options,
}: CreatePrototypeDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const latest = versions[0] ?? null;

    const form = useDesignForm({
        initial: {
            ...emptyCreatePrototypeInput,
            game_version_id: latest?.id ?? '',
        },
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: PROTOTYPE_NAME_MIN_LENGTH,
                    max: PROTOTYPE_NAME_MAX_LENGTH,
                    tooShort: t('Give the prototype a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            game_version_id:
                input.game_version_id === ''
                    ? t('Choose the design version this is based on.')
                    : undefined,
        }),
        perform: (input, mutation) =>
            createPrototype({ workspace, game }, input, {
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
                <Button data-test="create-prototype-button">
                    <Plus className="size-4" />
                    {t('New prototype')}
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{t('Start a prototype')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'A prototype is the buildable version of a design — the printed cards, the simulation, the box of parts. You cut versions of it as you rebuild.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {versions.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                        data-test="prototype-needs-version"
                    >
                        {t(
                            'This game has no versions yet. Cut one first — a prototype records which state of the design it was built to implement.',
                        )}
                    </p>
                ) : (
                    <form
                        className="grid gap-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.submit();
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="prototype-version">
                                {t('Built from')}
                            </Label>

                            <Select
                                value={form.input.game_version_id}
                                onValueChange={(value) =>
                                    form.setField('game_version_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="prototype-version"
                                    data-test="prototype-version-picker"
                                >
                                    <SelectValue
                                        placeholder={t('Choose a version')}
                                    />
                                </SelectTrigger>

                                <SelectContent>
                                    {versions.map((version) => (
                                        <SelectItem
                                            key={version.id}
                                            value={version.id}
                                        >
                                            {version.label}
                                            {version.name
                                                ? ` · ${version.name}`
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={form.errors.game_version_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="prototype-name">{t('Name')}</Label>

                            <Input
                                id="prototype-name"
                                value={form.input.name}
                                onChange={(event) =>
                                    form.setField('name', event.target.value)
                                }
                                placeholder={t('Core combat prototype')}
                                autoComplete="off"
                                data-test="prototype-name-input"
                            />

                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="prototype-type">
                                {t('What is it made of?')}
                            </Label>

                            <Select
                                value={form.input.type}
                                onValueChange={(value) =>
                                    form.setField(
                                        'type',
                                        value as PrototypeType,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="prototype-type"
                                    data-test="prototype-type-picker"
                                >
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    {options.types.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <p className="text-xs text-muted-foreground">
                                {
                                    options.types.find(
                                        (type) =>
                                            type.value === form.input.type,
                                    )?.description
                                }
                            </p>

                            <InputError message={form.errors.type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="prototype-description">
                                {t('Description')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Textarea
                                id="prototype-description"
                                value={form.input.description}
                                onChange={(event) =>
                                    form.setField(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'Printed cards, borrowed cubes and a hand-drawn board.',
                                )}
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
                                data-test="submit-create-prototype-button"
                            >
                                {form.processing && <Spinner />}
                                {t('Start prototype')}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
