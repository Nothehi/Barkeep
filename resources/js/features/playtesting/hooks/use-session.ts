import { useCallback, useState } from 'react';
import {
    cancelSession,
    completeSession,
    startSession,
    updateSession,
} from '../api';
import type { CompleteSessionInput } from '../schemas/playtest';
import type { PlaytestSession, SessionPermissions } from '../types/playtest';
import { useElapsedTime } from './use-elapsed-time';
import { useSessionPermissions } from './use-session-permissions';

export type UseSessionResult = {
    session: PlaytestSession;
    permissions: SessionPermissions;
    processing: boolean;
    errors: Record<string, string | undefined>;

    /** Whether the session is happening right now. */
    isRunning: boolean;

    /**
     * Seconds since the session started, ticking, or null when there is
     * nothing to count.
     */
    elapsed: number | null;

    start: () => void;
    complete: (input: CompleteSessionInput) => void;
    cancel: () => void;
    saveNotes: (notes: string) => void;
};

/**
 * One sitting, and everything that can be done to it.
 *
 * This is the hook the live screen is built on, so every action is one call
 * with no arguments to think about. Ending a session in particular has to be
 * pressable while people are standing up and putting the box away.
 */
export function useSession(
    workspace: string,
    game: string,
    playtest: string,
    session: PlaytestSession,
): UseSessionResult {
    const permissions = useSessionPermissions(session);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );

    const isRunning = session.status === 'in_progress';
    const elapsed = useElapsedTime(isRunning ? session.started_at : null);

    const run = useCallback((action: (done: () => void) => void) => {
        setProcessing(true);
        setErrors({});
        action(() => setProcessing(false));
    }, []);

    const start = useCallback(() => {
        run((done) =>
            startSession(workspace, game, playtest, session.id, {
                onError: setErrors,
                onFinish: done,
            }),
        );
    }, [run, workspace, game, playtest, session.id]);

    const complete = useCallback(
        (input: CompleteSessionInput) => {
            run((done) =>
                completeSession(workspace, game, playtest, session.id, input, {
                    onError: setErrors,
                    onFinish: done,
                }),
            );
        },
        [run, workspace, game, playtest, session.id],
    );

    const cancel = useCallback(() => {
        run((done) =>
            cancelSession(workspace, game, playtest, session.id, {
                onError: setErrors,
                onFinish: done,
            }),
        );
    }, [run, workspace, game, playtest, session.id]);

    const saveNotes = useCallback(
        (notes: string) => {
            run((done) =>
                updateSession(
                    workspace,
                    game,
                    playtest,
                    session.id,
                    { notes },
                    { onError: setErrors, onFinish: done },
                ),
            );
        },
        [run, workspace, game, playtest, session.id],
    );

    return {
        session,
        permissions,
        processing,
        errors,
        isRunning,
        elapsed,
        start,
        complete,
        cancel,
        saveNotes,
    };
}
