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
import { createVersion } from '../api';
import type { Framework, FrameworkVersion } from '../types/framework';

type CreateVersionDialogProps = {
    framework: Framework;
    versions: FrameworkVersion[];
};

/**
 * Cuts the next edition of a methodology.
 *
 * There is no version number field. The server allocates the next number in
 * sequence, so an edition number always means the same thing to the studios
 * reading it — and the dialog says which number is coming so it is not a
 * surprise.
 *
 * Nothing is copied forward from the previous edition. That is a real
 * limitation rather than a design flourish, and saying so here is better than
 * letting an author publish an empty v2 and find out afterwards.
 */
export default function CreateVersionDialog({
    framework,
    versions,
}: CreateVersionDialogProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');

    if (!framework.permissions.canCreateVersion) {
        return null;
    }

    const nextNumber =
        versions.reduce(
            (highest, edition) => Math.max(highest, edition.version_number),
            0,
        ) + 1;

    const close = (next: boolean) => {
        setOpen(next);

        if (!next) {
            setName('');
            setDescription('');
            setErrors({});
        }
    };

    return (
        <Dialog open={open} onOpenChange={close}>
            <DialogTrigger asChild>
                <Button data-test="create-version-button">
                    <Plus className="size-4" />
                    New edition
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cut v{nextNumber}</DialogTitle>
                    <DialogDescription>
                        Edition numbers are assigned in order, so this will be v
                        {nextNumber}. It starts empty — nothing is carried over
                        from the previous edition.
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        setProcessing(true);
                        setErrors({});

                        createVersion(
                            framework.slug,
                            {
                                name: name.trim() || null,
                                description: description.trim() || null,
                            },
                            {
                                onSuccess: () => close(false),
                                onError: setErrors,
                                onFinish: () => setProcessing(false),
                            },
                        );
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="version-name">Name (optional)</Label>

                        <Input
                            id="version-name"
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            placeholder="The one with the playtesting phase"
                            autoFocus
                            data-test="version-name-input"
                        />

                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="version-description">
                            What changed
                        </Label>

                        <Textarea
                            id="version-description"
                            value={description}
                            onChange={(event) =>
                                setDescription(event.target.value)
                            }
                            rows={4}
                            data-test="version-description-input"
                        />

                        <InputError message={errors.description} />
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
                            disabled={processing}
                            data-test="version-submit"
                        >
                            {processing && <Spinner />}
                            Cut v{nextNumber}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
