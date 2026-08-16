import { useCallback, useMemo, useState } from 'react';
import { createSession } from '../api';
import type { CreateSessionInput } from '../schemas/playtest';
import { emptyCreateSessionInput } from '../schemas/playtest';
import type { PlaytestSession } from '../types/playtest';

export type UsePlaytestSessionsResult = {
    sessions: PlaytestSession[];

    /** The sitting that is happening right now, if one is. */
    active: PlaytestSession | null;

    completedCount: number;
    input: CreateSessionInput;
    errors: Record<string, string | undefined>;
    processing: boolean;
    setField: <K extends keyof CreateSessionInput>(
        field: K,
        value: CreateSessionInput[K],
    ) => void;
    submit: () => void;
    reset: () => void;
};

/**
 * A playtest's sittings, and the form that schedules another.
 *
 * The form asks for nothing, and that is the point: the common case is a
 * designer about to run a session in the next thirty seconds, and a form that
 * insists on a location first is a form that gets abandoned — after which the
 * session gets run without being recorded.
 *
 * `active` exists so the playtest screen can put a running session at the top
 * where somebody standing at a table will find it.
 */
export function usePlaytestSessions(
    workspace: string,
    game: string,
    playtest: string,
    sessions: PlaytestSession[],
): UsePlaytestSessionsResult {
    const [input, setInput] = useState<CreateSessionInput>(
        emptyCreateSessionInput,
    );
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const setField = useCallback(
        <K extends keyof CreateSessionInput>(
            field: K,
            value: CreateSessionInput[K],
        ) => {
            setInput((current) => ({ ...current, [field]: value }));
        },
        [],
    );

    const reset = useCallback(() => {
        setInput(emptyCreateSessionInput);
        setErrors({});
    }, []);

    const submit = useCallback(() => {
        setProcessing(true);
        setErrors({});

        createSession(workspace, game, playtest, input, {
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, playtest, input]);

    const active = useMemo(
        () =>
            sessions.find((session) => session.status === 'in_progress') ??
            null,
        [sessions],
    );

    const completedCount = useMemo(
        () =>
            sessions.filter((session) => session.status === 'completed').length,
        [sessions],
    );

    return {
        sessions,
        active,
        completedCount,
        input,
        errors,
        processing,
        setField,
        submit,
        reset,
    };
}
