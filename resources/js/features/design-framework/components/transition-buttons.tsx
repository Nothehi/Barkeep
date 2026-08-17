import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { FrameworkTransition } from '../types/framework';

type TransitionButtonsProps = {
    transitions: FrameworkTransition[];
    onMove: (status: string, done: () => void) => void;
    testPrefix: string;
};

/**
 * The lifecycle moves something can currently make.
 *
 * Deliberately not a dropdown of every status. A draft edition does not go
 * straight to archived, and offering a select that lets somebody try would
 * mean the interface proposing things the server will refuse.
 *
 * The list arrives from the server already worded, because the wording depends
 * on both ends — reaching active from paused is "Resume framework", not "Make
 * active" — and because a caller who may not make a move is sent none rather
 * than being sent one to be told off for pressing.
 */
export default function TransitionButtons({
    transitions,
    onMove,
    testPrefix,
}: TransitionButtonsProps) {
    const [pending, setPending] = useState<string | null>(null);

    if (transitions.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {transitions.map((transition, index) => (
                <Button
                    key={transition.status}
                    size="sm"
                    variant={index === 0 ? 'default' : 'outline'}
                    disabled={pending !== null}
                    onClick={() => {
                        setPending(transition.status);
                        onMove(transition.status, () => setPending(null));
                    }}
                    data-test={`${testPrefix}-${transition.status}`}
                >
                    {pending === transition.status && <Spinner />}
                    {transition.label}
                </Button>
            ))}
        </div>
    );
}
