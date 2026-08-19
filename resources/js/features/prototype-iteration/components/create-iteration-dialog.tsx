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
import { createIteration } from '../api';
import { useDesignForm } from '../hooks/use-design-form';
import {
    emptyCreateIterationInput,
    ITERATION_OBJECTIVE_MAX_LENGTH,
    ITERATION_OBJECTIVE_MIN_LENGTH,
    ITERATION_TITLE_MAX_LENGTH,
    ITERATION_TITLE_MIN_LENGTH,
    validateLength,
} from '../schemas/prototype-iteration';
import type { PrototypeVersion } from '../types/prototype-iteration';

type CreateIterationDialogProps = {
    workspace: string;
    game: string;
    versions: GameVersion[];
    prototypeVersions: PrototypeVersion[];
};

/**
 * Plans one turn of the design loop.
 *
 * Two pickers, and getting both right is what the whole module rests on. An iteration says "we worked on
 * *this* design, using *this* build" — and the two are separate because they move independently: a studio can
 * rebuild the prototype three times against one design version, or take one build to two designs.
 *
 * Both default to the newest of their kind, which is what a designer means when they start a cycle without
 * thinking about it. Neither picker can offer anything from another project: the server resolves both through
 * this game and refuses anything else, so a mismatch is impossible rather than merely unlikely.
 */
export default function CreateIterationDialog({
    workspace,
    game,
    versions,
    prototypeVersions,
}: CreateIterationDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const latestVersion = versions[0] ?? null;
    const latestBuild = prototypeVersions[0] ?? null;

    const form = useDesignForm({
        initial: {
            ...emptyCreateIterationInput,
            game_version_id: latestVersion?.id ?? '',
            prototype_version_id: latestBuild?.id ?? '',
        },
        validate: (input) => ({
            title:
                validateLength(input.title, {
                    min: ITERATION_TITLE_MIN_LENGTH,
                    max: ITERATION_TITLE_MAX_LENGTH,
                    tooShort: t('Give the iteration a title.'),
                    tooLong: t('That title is too long.'),
                }) ?? undefined,
            objective:
                validateLength(input.objective, {
                    min: ITERATION_OBJECTIVE_MIN_LENGTH,
                    max: ITERATION_OBJECTIVE_MAX_LENGTH,
                    tooShort: t('Say what this iteration is meant to change.'),
                    tooLong: t('That objective is too long.'),
                }) ?? undefined,
            game_version_id:
                input.game_version_id === ''
                    ? t('Choose the design version this is based on.')
                    : undefined,
            prototype_version_id:
                input.prototype_version_id === ''
                    ? t('Choose the prototype version this iteration is about.')
                    : undefined,
        }),
        perform: (input, mutation) =>
            createIteration({ workspace, game }, input, {
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

    const canPlan = versions.length > 0 && prototypeVersions.length > 0;

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogTrigger asChild>
                <Button data-test="create-iteration-button">
                    <Plus className="size-4" />
                    {t('New iteration')}
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{t('Plan an iteration')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'Say what you want to change and why. You record what you changed, what you tested and what you decided as the cycle runs.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {!canPlan ? (
                    <p
                        className="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                        data-test="iteration-needs-prototype"
                    >
                        {t(
                            'An iteration needs a design version and a prototype version to work against. Cut a game version and a prototype version first.',
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
                            <Label htmlFor="iteration-prototype-version">
                                {t('Prototype version')}
                            </Label>

                            <Select
                                value={form.input.prototype_version_id}
                                onValueChange={(value) =>
                                    form.setField('prototype_version_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="iteration-prototype-version"
                                    data-test="iteration-build-picker"
                                >
                                    <SelectValue
                                        placeholder={t(
                                            'Choose a prototype version',
                                        )}
                                    />
                                </SelectTrigger>

                                <SelectContent>
                                    {prototypeVersions.map((build) => (
                                        <SelectItem
                                            key={build.id}
                                            value={build.id}
                                        >
                                            {build.prototype_name
                                                ? `${build.prototype_name} · ${build.label}`
                                                : build.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <p className="text-xs text-muted-foreground">
                                {t('What was actually on the table.')}
                            </p>

                            <InputError
                                message={form.errors.prototype_version_id}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="iteration-version">
                                {t('Design version')}
                            </Label>

                            <Select
                                value={form.input.game_version_id}
                                onValueChange={(value) =>
                                    form.setField('game_version_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="iteration-version"
                                    data-test="iteration-version-picker"
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

                            <p className="text-xs text-muted-foreground">
                                {t('The design as it stands.')}
                            </p>

                            <InputError message={form.errors.game_version_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="iteration-title">
                                {t('Title')}
                            </Label>

                            <Input
                                id="iteration-title"
                                value={form.input.title}
                                onChange={(event) =>
                                    form.setField('title', event.target.value)
                                }
                                placeholder={t('Improve combat pacing')}
                                autoComplete="off"
                                data-test="iteration-title-input"
                            />

                            <InputError message={form.errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="iteration-objective">
                                {t('What are you trying to change?')}
                            </Label>

                            <Textarea
                                id="iteration-objective"
                                value={form.input.objective}
                                onChange={(event) =>
                                    form.setField(
                                        'objective',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'Reduce the time players spend waiting between decisions.',
                                )}
                                rows={3}
                                data-test="iteration-objective-input"
                            />

                            <InputError message={form.errors.objective} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="iteration-hypothesis">
                                {t('What do you expect to happen?')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Textarea
                                id="iteration-hypothesis"
                                value={form.input.hypothesis}
                                onChange={(event) =>
                                    form.setField(
                                        'hypothesis',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'If combat decisions are simultaneous, downtime will decrease.',
                                )}
                                rows={2}
                            />

                            <InputError message={form.errors.hypothesis} />

                            <p className="text-xs text-muted-foreground">
                                {t(
                                    'Writing this down first is what makes the outcome mean something afterwards.',
                                )}
                            </p>
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
                                data-test="submit-create-iteration-button"
                            >
                                {form.processing && <Spinner />}
                                {t('Plan iteration')}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
