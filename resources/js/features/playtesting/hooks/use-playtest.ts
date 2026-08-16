import { useCallback, useState } from 'react';
import { cancelPlaytest, completePlaytest, updatePlaytest } from '../api';
import type { Playtest, PlaytestPermissions } from '../types/playtest';
import { usePlaytestPermissions } from './use-playtest-permissions';

export type UsePlaytestResult = {
    playtest: Playtest;
    permissions: PlaytestPermissions;
    processing: boolean;
    errors: Record<string, string | undefined>;

    /** Close the investigation, optionally saying what it concluded. */
    complete: (conclusion: string) => void;

    /** Call it off. Its sessions are left exactly as they are. */
    cancel: () => void;

    /**
     * Write down what was learned.
     *
     * Separate from the plan on purpose, and the one thing a completed
     * playtest still accepts — conclusions are drawn after the sessions are
     * over, often days later.
     */
    recordConclusion: (conclusion: string) => void;
};

/**
 * One playtest, and the things that can be done to it.
 *
 * The playtest arrives with the page and every action is an Inertia visit, so
 * what is on screen after one is whatever the server actually wrote — there is
 * no local copy to reconcile.
 */
export function usePlaytest(
    workspace: string,
    game: string,
    playtest: Playtest,
): UsePlaytestResult {
    const permissions = usePlaytestPermissions(playtest);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );

    const complete = useCallback(
        (conclusion: string) => {
            setProcessing(true);
            setErrors({});

            completePlaytest(workspace, game, playtest.id, conclusion, {
                onError: setErrors,
                onFinish: () => setProcessing(false),
            });
        },
        [workspace, game, playtest.id],
    );

    const cancel = useCallback(() => {
        setProcessing(true);
        setErrors({});

        cancelPlaytest(workspace, game, playtest.id, {
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, playtest.id]);

    const recordConclusion = useCallback(
        (conclusion: string) => {
            setProcessing(true);
            setErrors({});

            updatePlaytest(
                workspace,
                game,
                playtest.id,
                { conclusion },
                {
                    onError: setErrors,
                    onFinish: () => setProcessing(false),
                },
            );
        },
        [workspace, game, playtest.id],
    );

    return {
        playtest,
        permissions,
        processing,
        errors,
        complete,
        cancel,
        recordConclusion,
    };
}
