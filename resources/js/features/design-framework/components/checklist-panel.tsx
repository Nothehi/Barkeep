import { useState } from 'react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { completeChecklistItem } from '../api';
import type { ChecklistProgress } from '../types/framework';

type ChecklistPanelProps = {
    workspace: string;
    game: string;
    checklists: ChecklistProgress[];
    canRecord: boolean;
};

/**
 * The requirements this phase sets, and which of them this game has met.
 *
 * A checklist is read as a unit, so its own count sits above the boxes rather
 * than being folded into the phase total — "2 of 4" is what somebody wants
 * when they are working through a list.
 *
 * Two counts, and the difference between them matters. The one shown is the
 * required one, because that is what progress is measured on; optional items
 * are tickable and are not in it, which is what lets an author add a
 * nice-to-have without everybody's numbers moving.
 */
export default function ChecklistPanel({
    workspace,
    game,
    checklists,
    canRecord,
}: ChecklistPanelProps) {
    const [pending, setPending] = useState<string | null>(null);

    if (checklists.length === 0) {
        return null;
    }

    return (
        <section className="space-y-3" data-test="checklist-panel">
            <h2 className="text-sm font-medium">Checklists</h2>

            <div className="grid gap-3">
                {checklists.map((entry) => {
                    const checklist = entry.checklist;
                    const items = checklist.items ?? [];
                    const state = new Map(
                        entry.items.map((item) => [
                            item.checklist_item_id,
                            item.is_complete,
                        ]),
                    );

                    return (
                        <Card
                            key={checklist.id}
                            data-test={`checklist-${checklist.id}`}
                        >
                            <CardHeader className="gap-1">
                                <div className="flex flex-wrap items-baseline justify-between gap-2">
                                    <span className="font-medium">
                                        {checklist.title}
                                    </span>

                                    <span className="text-xs text-muted-foreground">
                                        {entry.required.completed} of{' '}
                                        {entry.required.total} required
                                    </span>
                                </div>

                                {checklist.description && (
                                    <span className="text-sm text-muted-foreground">
                                        {checklist.description}
                                    </span>
                                )}
                            </CardHeader>

                            <CardContent className="space-y-2">
                                {items.map((item) => (
                                    <label
                                        key={item.id}
                                        className="flex items-start gap-3"
                                    >
                                        <Checkbox
                                            checked={
                                                state.get(item.id) ?? false
                                            }
                                            disabled={
                                                !canRecord ||
                                                pending === item.id
                                            }
                                            onCheckedChange={(checked) => {
                                                setPending(item.id);

                                                completeChecklistItem(
                                                    workspace,
                                                    game,
                                                    item.id,
                                                    checked === true,
                                                    null,
                                                    {
                                                        onFinish: () =>
                                                            setPending(null),
                                                    },
                                                );
                                            }}
                                            data-test={`checklist-item-${item.id}`}
                                        />

                                        <span className="min-w-0 space-y-0.5">
                                            <span className="block text-sm">
                                                {item.title}

                                                {!item.required && (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        optional
                                                    </span>
                                                )}
                                            </span>

                                            {item.description && (
                                                <span className="block text-xs text-muted-foreground">
                                                    {item.description}
                                                </span>
                                            )}
                                        </span>
                                    </label>
                                ))}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </section>
    );
}
