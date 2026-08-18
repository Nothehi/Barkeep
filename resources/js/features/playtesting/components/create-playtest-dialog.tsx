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
import { useCreatePlaytest } from '../hooks/use-create-playtest';

type CreatePlaytestDialogProps = {
    workspace: string;
    game: string;
    versions: GameVersion[];
};

/**
 * Plans a playtest against a version of the game.
 *
 * The version picker is the field that makes this more than a note-taking
 * form: a playtest is evidence about one iteration of a design, and choosing
 * the wrong one produces a record of an evening nobody had. It defaults to the
 * newest version, which is what a designer means when they say "test the
 * current build".
 *
 * There is no version field when the game has none, because there is nothing
 * to test yet — the dialog says so rather than offering an empty picker.
 */
export default function CreatePlaytestDialog({
    workspace,
    game,
    versions,
}: CreatePlaytestDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const latest = versions[0] ?? null;
    const form = useCreatePlaytest(workspace, game, latest?.id ?? null);

    const close = (next: boolean) => {
        setOpen(next);

        if (!next) {
            form.reset();
        }
    };

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogTrigger asChild>
                <Button data-test="create-playtest-button">
                    <Plus className="size-4" />
                    {t('New playtest')}
                </Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{t('Plan a playtest')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'Say what you want to find out and which version you are finding it out about. You can add sessions once it exists.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                {versions.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                        data-test="playtest-needs-version"
                    >
                        {t(
                            'This game has no versions yet. Cut one first — a playtest records what was on the table, so it has to point at a specific iteration.',
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
                            <Label htmlFor="playtest-version">
                                {t('Version under test')}
                            </Label>

                            <Select
                                value={form.input.game_version_id}
                                onValueChange={(value) =>
                                    form.setField('game_version_id', value)
                                }
                            >
                                <SelectTrigger
                                    id="playtest-version"
                                    data-test="playtest-version-picker"
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
                            <Label htmlFor="playtest-title">{t('Title')}</Label>

                            <Input
                                id="playtest-title"
                                value={form.input.title}
                                onChange={(event) =>
                                    form.setField('title', event.target.value)
                                }
                                placeholder={t('First-player advantage')}
                                autoComplete="off"
                                data-test="playtest-title-input"
                            />

                            <InputError message={form.errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="playtest-objective">
                                {t('What do you want to find out?')}
                            </Label>

                            <Textarea
                                id="playtest-objective"
                                value={form.input.objective}
                                onChange={(event) =>
                                    form.setField(
                                        'objective',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'Determine whether the first-player advantage is too strong at four players.',
                                )}
                                rows={3}
                                data-test="playtest-objective-input"
                            />

                            <InputError message={form.errors.objective} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="playtest-hypothesis">
                                {t('What do you expect to happen?')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Textarea
                                id="playtest-hypothesis"
                                value={form.input.hypothesis}
                                onChange={(event) =>
                                    form.setField(
                                        'hypothesis',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'Going first is worth about a turn, which is more than the catch-up bonus covers.',
                                )}
                                rows={2}
                            />

                            <InputError message={form.errors.hypothesis} />

                            <p className="text-xs text-muted-foreground">
                                {t(
                                    'Writing this down first is what makes the result mean something afterwards.',
                                )}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="playtest-planned-at">
                                {t('Planned for')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Input
                                id="playtest-planned-at"
                                type="date"
                                value={form.input.planned_at}
                                onChange={(event) =>
                                    form.setField(
                                        'planned_at',
                                        event.target.value,
                                    )
                                }
                            />

                            <InputError message={form.errors.planned_at} />
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
                                data-test="submit-create-playtest-button"
                            >
                                {form.processing && <Spinner />}
                                {t('Plan playtest')}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
