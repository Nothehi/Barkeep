import { Pencil } from 'lucide-react';
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
import { useGamePermissions } from '../hooks/use-game-permissions';
import { useUpdateGame } from '../hooks/use-update-game';
import type { Game } from '../types/game';

type EditGameDialogProps = {
    game: Game;
    workspace: string;
};

/**
 * Edits a game's name, address and description in place.
 *
 * The same three fields as the settings screen, offered where somebody is
 * already looking at the game. Status and phase are absent because both are
 * explicit actions with their own rules, not fields on a form.
 */
export default function EditGameDialog({
    game,
    workspace,
}: EditGameDialogProps) {
    const permissions = useGamePermissions(game);
    const [open, setOpen] = useState(false);
    const form = useUpdateGame(workspace, game);

    if (!permissions.canUpdate) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    data-test="edit-game-button"
                >
                    <Pencil className="size-4" />
                    Edit
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit {game.name}</DialogTitle>
                    <DialogDescription>
                        Changing the address changes every link to this game.
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
                        <Label htmlFor="edit-game-name">Name</Label>

                        <Input
                            id="edit-game-name"
                            value={form.input.name}
                            onChange={(event) =>
                                form.setName(event.target.value)
                            }
                            required
                        />

                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-game-slug">Address</Label>

                        <Input
                            id="edit-game-slug"
                            value={form.input.slug}
                            onChange={(event) =>
                                form.setSlug(event.target.value)
                            }
                            autoComplete="off"
                            spellCheck={false}
                            required
                        />

                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="edit-game-description">
                            Description
                        </Label>

                        <Textarea
                            id="edit-game-description"
                            value={form.input.description}
                            onChange={(event) =>
                                form.setDescription(event.target.value)
                            }
                            rows={3}
                        />

                        <InputError message={form.errors.description} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </Button>

                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                !form.isValid ||
                                !form.isDirty
                            }
                            data-test="submit-edit-game-button"
                        >
                            {form.processing && <Spinner />}
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
