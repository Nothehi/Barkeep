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
import { useCreateGame } from '../hooks/use-create-game';
import type { DesignPhase, GameOptions } from '../types/game';

type CreateGameDialogProps = {
    workspace: string;
    options: GameOptions;
};

/**
 * Starts a new game.
 *
 * Four fields, and no status among them: a game always begins as a draft. The
 * phase is offered because plenty of designers have a prototype in a drawer
 * long before they write anything down, and making them create an "idea" and
 * immediately move it would be a pointless step.
 *
 * On success the server redirects into the new game, so there is nothing to
 * close — the dialog goes with the page.
 */
export default function CreateGameDialog({
    workspace,
    options,
}: CreateGameDialogProps) {
    const [open, setOpen] = useState(false);
    const form = useCreateGame(workspace);

    const close = (next: boolean) => {
        setOpen(next);

        if (!next) {
            form.reset();
        }
    };

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogTrigger asChild>
                <Button data-test="create-game-button">
                    <Plus className="size-4" />
                    New game
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Start a game</DialogTitle>
                    <DialogDescription>
                        It begins as a draft, visible only to this workspace.
                        Everything here can be changed later.
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
                        <Label htmlFor="game-name">Name</Label>

                        <Input
                            id="game-name"
                            value={form.input.name}
                            onChange={(event) =>
                                form.setName(event.target.value)
                            }
                            placeholder="Bears &amp; Bridges"
                            autoComplete="off"
                            required
                        />

                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="game-slug">Address</Label>

                        <Input
                            id="game-slug"
                            value={form.input.slug}
                            onChange={(event) =>
                                form.setSlug(event.target.value)
                            }
                            placeholder="bears-and-bridges"
                            autoComplete="off"
                            spellCheck={false}
                        />

                        <p className="text-sm text-muted-foreground">
                            Leave this blank and we will pick one from the name.
                        </p>

                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="game-design-phase">Design phase</Label>

                        <Select
                            value={form.input.design_phase}
                            onValueChange={(value) =>
                                form.setDesignPhase(value as DesignPhase)
                            }
                        >
                            <SelectTrigger
                                id="game-design-phase"
                                className="w-full"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                {options.design_phases.map((phase) => (
                                    <SelectItem
                                        key={phase.value}
                                        value={phase.value}
                                    >
                                        {phase.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <InputError message={form.errors.design_phase} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="game-description">Description</Label>

                        <Textarea
                            id="game-description"
                            value={form.input.description}
                            onChange={(event) =>
                                form.setDescription(event.target.value)
                            }
                            placeholder="What is it, in a sentence?"
                            rows={3}
                        />

                        <InputError message={form.errors.description} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => close(false)}
                        >
                            Cancel
                        </Button>

                        <Button
                            type="submit"
                            disabled={form.processing || !form.isValid}
                            data-test="submit-create-game-button"
                        >
                            {form.processing && <Spinner />}
                            Create game
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
