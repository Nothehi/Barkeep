import { useState } from 'react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { completePractice } from '../api';
import type { DesignPractice, PracticeCompletion } from '../types/framework';

type PracticeListProps = {
    workspace: string;
    game: string;
    practices: DesignPractice[];
    completions: PracticeCompletion[];
    canRecord: boolean;
};

/**
 * The activities this phase asks for, and which of them this game has done.
 *
 * A tick is a toggle in both directions: unticking removes this studio's
 * completion rather than deleting anything belonging to the methodology, which
 * is why it is the same call with `completed: false` rather than a delete.
 *
 * The instructions are shown in full rather than truncated. A practice is
 * something somebody is about to go and do, and hiding half of it behind a
 * "read more" is the wrong economy on the screen where they do it.
 */
export default function PracticeList({
    workspace,
    game,
    practices,
    completions,
    canRecord,
}: PracticeListProps) {
    const [pending, setPending] = useState<string | null>(null);

    const done = new Set(
        completions.map((completion) => completion.practice_id),
    );

    if (practices.length === 0) {
        return null;
    }

    return (
        <section className="space-y-3" data-test="practice-list">
            <h2 className="text-sm font-medium">Practices</h2>

            <div className="grid gap-3">
                {practices.map((practice) => {
                    const complete = done.has(practice.id);

                    return (
                        <Card
                            key={practice.id}
                            data-test={`practice-${practice.id}`}
                        >
                            <CardHeader className="gap-1">
                                <label className="flex items-start gap-3">
                                    <Checkbox
                                        checked={complete}
                                        disabled={
                                            !canRecord ||
                                            pending === practice.id
                                        }
                                        onCheckedChange={(checked) => {
                                            setPending(practice.id);

                                            completePractice(
                                                workspace,
                                                game,
                                                practice.id,
                                                checked === true,
                                                null,
                                                {
                                                    onFinish: () =>
                                                        setPending(null),
                                                },
                                            );
                                        }}
                                        data-test={`practice-toggle-${practice.id}`}
                                    />

                                    <span className="min-w-0 space-y-1">
                                        <span className="block font-medium">
                                            {practice.title}
                                        </span>

                                        {practice.description && (
                                            <span className="block text-sm text-muted-foreground">
                                                {practice.description}
                                            </span>
                                        )}
                                    </span>
                                </label>
                            </CardHeader>

                            {practice.instructions && (
                                <CardContent>
                                    <p className="text-sm whitespace-pre-line text-muted-foreground">
                                        {practice.instructions}
                                    </p>
                                </CardContent>
                            )}
                        </Card>
                    );
                })}
            </div>
        </section>
    );
}
