import { useCallback, useState } from 'react';
import { createPlaytest } from '../api';
import type { CreatePlaytestInput } from '../schemas/playtest';
import {
    emptyCreatePlaytestInput,
    validatePlaytestObjective,
    validatePlaytestTitle,
} from '../schemas/playtest';

export type UseCreatePlaytestResult = {
    input: CreatePlaytestInput;
    errors: Record<string, string | undefined>;
    processing: boolean;
    setField: <K extends keyof CreatePlaytestInput>(
        field: K,
        value: CreatePlaytestInput[K],
    ) => void;
    submit: () => void;
    reset: () => void;
};

/**
 * The form that plans a playtest.
 *
 * The client-side checks are there to catch a blank objective before a round
 * trip; the server validates every field again and its answer replaces
 * whatever this decided. That is why a failed submit clears the local errors
 * first — showing both sets at once would leave somebody reading two opinions
 * about the same field.
 *
 * @param version the version to test unless somebody picks another, normally
 *                the game's latest — which is what a designer means when they
 *                say "test the current build"
 */
export function useCreatePlaytest(
    workspace: string,
    game: string,
    version: string | null,
): UseCreatePlaytestResult {
    const initial: CreatePlaytestInput = {
        ...emptyCreatePlaytestInput,
        game_version_id: version ?? '',
    };

    const [input, setInput] = useState<CreatePlaytestInput>(initial);
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const setField = useCallback(
        <K extends keyof CreatePlaytestInput>(
            field: K,
            value: CreatePlaytestInput[K],
        ) => {
            setInput((current) => ({ ...current, [field]: value }));
        },
        [],
    );

    const reset = useCallback(() => {
        setInput({
            ...emptyCreatePlaytestInput,
            game_version_id: version ?? '',
        });
        setErrors({});
    }, [version]);

    const submit = useCallback(() => {
        const local: Record<string, string | undefined> = {
            title: validatePlaytestTitle(input.title) ?? undefined,
            objective: validatePlaytestObjective(input.objective) ?? undefined,
            game_version_id:
                input.game_version_id === ''
                    ? 'Choose the version being tested.'
                    : undefined,
        };

        if (Object.values(local).some((message) => message !== undefined)) {
            setErrors(local);

            return;
        }

        setProcessing(true);
        setErrors({});

        createPlaytest(workspace, game, input, {
            preserveScroll: false,
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, input]);

    return { input, errors, processing, setField, submit, reset };
}
