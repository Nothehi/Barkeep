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
import { createMechanic } from '../api/mechanics';
import type { MechanicCategory, MechanicOptions } from '../types/mechanic';

type CreateMechanicDialogProps = {
    options: MechanicOptions;
};

/**
 * Adds a term to the shared vocabulary.
 *
 * There is no address field. The server derives the slug from the name and
 * resolves collisions itself, so two curators typing "Worker Placement" and
 * "worker placement" cannot produce two rows that mean the same thing.
 *
 * The dialog says what the term is for rather than what it is called, because
 * the definition is the part that has to be agreed: a vocabulary whose entries
 * are bare names is one where every studio quietly means something different.
 */
export default function CreateMechanicDialog({
    options,
}: CreateMechanicDialogProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [category, setCategory] = useState<MechanicCategory>(
        options.categories[0]?.value ?? 'turn_structure',
    );

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
                <Button data-test="create-mechanic-button">
                    <Plus className="size-4" />
                    Add mechanic
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add a mechanic</DialogTitle>
                    <DialogDescription>
                        This word becomes available to every game on the
                        platform. Define it well enough that two designers would
                        agree on what it means.
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        setProcessing(true);
                        setErrors({});

                        createMechanic(
                            {
                                name,
                                description: description.trim() || null,
                                category,
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
                        <Label htmlFor="mechanic-name">Name</Label>

                        <Input
                            id="mechanic-name"
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            placeholder="Worker placement"
                            autoFocus
                            data-test="mechanic-name-input"
                        />

                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="mechanic-category">Category</Label>

                        <Select
                            value={category}
                            onValueChange={(value) =>
                                setCategory(value as MechanicCategory)
                            }
                        >
                            <SelectTrigger
                                id="mechanic-category"
                                data-test="mechanic-category-select"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                {options.categories.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <InputError message={errors.category} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="mechanic-description">Definition</Label>

                        <Textarea
                            id="mechanic-description"
                            value={description}
                            onChange={(event) =>
                                setDescription(event.target.value)
                            }
                            placeholder="What a designer is claiming when they pick this."
                            rows={4}
                            data-test="mechanic-description-input"
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
                            data-test="mechanic-submit"
                        >
                            {processing && <Spinner />}
                            Add mechanic
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
