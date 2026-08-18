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
import { useGamePermissions } from '../hooks/use-game-permissions';
import { useGameVersions } from '../hooks/use-game-versions';
import type { Game, GameVersion } from '../types/game';

type CreateVersionDialogProps = {
    game: Game;
    workspace: string;
    versions: GameVersion[];
};

/**
 * Records a new iteration of a game.
 *
 * There is no version number field, and that is the rule rather than an
 * omission: the server allocates the next number in sequence, so nobody can
 * claim v999 or reuse a number that already means something to the people who
 * played it. The dialog says which number is coming so it is not a surprise.
 */
export default function CreateVersionDialog({
    game,
    workspace,
    versions,
}: CreateVersionDialogProps) {
    const permissions = useGamePermissions(game);
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useGameVersions(workspace, game.slug, versions);

    if (!permissions.canCreateVersion) {
        return null;
    }

    const nextNumber = (versions[0]?.version_number ?? 0) + 1;

    const close = (next: boolean) => {
        setOpen(next);

        if (!next) {
            form.reset();
        }
    };

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogTrigger asChild>
                <Button data-test="create-version-button">
                    <Plus className="size-4" />
                    {t('New version')}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('Cut v:number', { number: nextNumber })}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'Version numbers are assigned in order, so this will be v:number. Say what changed while you still remember.',
                            { number: nextNumber },
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
                        <Label htmlFor="version-name">
                            {t('Name (optional)')}
                        </Label>

                        <Input
                            id="version-name"
                            value={form.input.name}
                            onChange={(event) =>
                                form.setName(event.target.value)
                            }
                            placeholder={t('Convention build')}
                            autoComplete="off"
                        />

                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="version-description">
                            {t('What changed')}
                        </Label>

                        <Textarea
                            id="version-description"
                            value={form.input.description}
                            onChange={(event) =>
                                form.setDescription(event.target.value)
                            }
                            placeholder={t(
                                'Trimmed the endgame, dropped the third resource.',
                            )}
                            rows={4}
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
                            data-test="submit-create-version-button"
                        >
                            {form.processing && <Spinner />}
                            {t('Create v:number', { number: nextNumber })}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
