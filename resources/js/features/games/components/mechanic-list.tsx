import { Archive } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { archiveMechanic } from '../api/mechanics';
import type { Mechanic, MechanicOptions } from '../types/mechanic';

type MechanicListProps = {
    mechanics: Mechanic[];
    options: MechanicOptions;
};

/**
 * The vocabulary, grouped by what each term is about.
 *
 * Grouped rather than listed flat because a vocabulary of any size is unusable
 * alphabetically: a designer scans for "how do turns work" and then for "what
 * do players do to each other", and a single A–Z makes them read the whole
 * thing to find out it holds nothing they need.
 *
 * The category order is the server's, and it is the order a design gets built
 * in rather than the alphabet — turn structure first, scoring last. A screen
 * that opened on "Scoring and endgame" would be asking the last question first.
 */
export default function MechanicList({
    mechanics,
    options,
}: MechanicListProps) {
    const [pending, setPending] = useState<string | null>(null);

    if (mechanics.length === 0) {
        return (
            <p
                className="rounded-lg border border-dashed px-4 py-12 text-center text-sm text-muted-foreground"
                data-test="mechanic-list-empty"
            >
                The vocabulary is empty. Nothing can describe itself yet.
            </p>
        );
    }

    const categories = [...options.categories].sort(
        (a, b) => a.position - b.position,
    );

    return (
        <div className="space-y-8" data-test="mechanic-list">
            {categories.map((category) => {
                const terms = mechanics.filter(
                    (mechanic) => mechanic.category === category.value,
                );

                if (terms.length === 0) {
                    return null;
                }

                return (
                    <section key={category.value} className="space-y-3">
                        <div className="space-y-0.5">
                            <h2 className="text-sm font-medium">
                                {category.label}
                            </h2>

                            <p className="text-sm text-muted-foreground">
                                {category.description}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            {terms.map((mechanic) => (
                                <div
                                    key={mechanic.id}
                                    className="flex items-start justify-between gap-3 rounded-md border px-3 py-2"
                                    data-test={`mechanic-${mechanic.slug}`}
                                >
                                    <div className="min-w-0 space-y-0.5">
                                        <p className="flex flex-wrap items-center gap-2 text-sm font-medium">
                                            {mechanic.name}

                                            {!mechanic.is_available && (
                                                <Badge variant="secondary">
                                                    {mechanic.status_label}
                                                </Badge>
                                            )}
                                        </p>

                                        {mechanic.description && (
                                            <p className="text-xs text-muted-foreground">
                                                {mechanic.description}
                                            </p>
                                        )}
                                    </div>

                                    {/*
                                     * Retiring is offered per term rather than
                                     * in a bulk action. Withdrawing a word
                                     * changes what is offered to every game on
                                     * the platform, and a checkbox column would
                                     * make that a thing done by accident.
                                     */}
                                    {mechanic.permissions.canArchive && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            disabled={pending !== null}
                                            onClick={() => {
                                                setPending(mechanic.id);

                                                archiveMechanic(mechanic.slug, {
                                                    onFinish: () =>
                                                        setPending(null),
                                                });
                                            }}
                                            data-test={`retire-${mechanic.slug}`}
                                        >
                                            {pending === mechanic.id ? (
                                                <Spinner />
                                            ) : (
                                                <Archive className="size-4" />
                                            )}
                                            Retire
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
