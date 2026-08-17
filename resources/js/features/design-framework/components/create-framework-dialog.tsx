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
import { createFramework } from '../api';

/**
 * Starts a new methodology.
 *
 * There is no address field, and that is the rule rather than an omission: the
 * server derives the slug from the name and resolves collisions itself, so two
 * people writing "Design Framework" on the same afternoon do not have to
 * negotiate over `/app/frameworks/design-framework`.
 *
 * A new framework is a draft with no editions. It becomes something a game can
 * adopt only once an edition inside it has been published, which is why this
 * dialog promises a place to write rather than a methodology.
 */
export default function CreateFrameworkDialog() {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');

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
                <Button data-test="create-framework-button">
                    <Plus className="size-4" />
                    New framework
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Write a framework</DialogTitle>
                    <DialogDescription>
                        A methodology starts as a draft with no editions.
                        Nothing can follow it until you publish one.
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        setProcessing(true);
                        setErrors({});

                        createFramework(
                            {
                                name,
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
                        <Label htmlFor="framework-name">Name</Label>

                        <Input
                            id="framework-name"
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            placeholder="Board Game Design Framework"
                            autoFocus
                            data-test="framework-name-input"
                        />

                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="framework-description">
                            Description
                        </Label>

                        <Textarea
                            id="framework-description"
                            value={description}
                            onChange={(event) =>
                                setDescription(event.target.value)
                            }
                            placeholder="What this methodology is for, and who it is aimed at."
                            rows={4}
                            data-test="framework-description-input"
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
                            disabled={processing || name.trim() === ''}
                            data-test="framework-submit"
                        >
                            {processing && <Spinner />}
                            Create framework
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
