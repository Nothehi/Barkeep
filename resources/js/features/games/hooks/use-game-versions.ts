import { useCallback, useMemo, useState } from 'react';
import { createGameVersion } from '../api';
import type { CreateGameVersionInput } from '../schemas/game';
import { emptyCreateGameVersionInput } from '../schemas/game';
import type { GameVersion } from '../types/game';

export type UseGameVersionsResult = {
    versions: GameVersion[];
    current: GameVersion | null;
    input: CreateGameVersionInput;
    errors: Record<string, string | undefined>;
    processing: boolean;
    setName: (name: string) => void;
    setDescription: (description: string) => void;
    submit: () => void;
    reset: () => void;
};

/**
 * A game's iterations, and the form that adds one.
 *
 * The list arrives with the page rather than being fetched, so it is already
 * ordered and scoped. "Current" is simply the first entry — the server orders
 * by version number, which is the ordering the domain guarantees.
 *
 * The form sends no version number. There is no field for one because the
 * server allocates it, and it redirects to whichever number it chose.
 */
export function useGameVersions(
    workspace: string,
    game: string,
    versions: GameVersion[],
): UseGameVersionsResult {
    const [input, setInput] = useState<CreateGameVersionInput>(
        emptyCreateGameVersionInput,
    );
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const setName = useCallback((name: string) => {
        setInput((current) => ({ ...current, name }));
    }, []);

    const setDescription = useCallback((description: string) => {
        setInput((current) => ({ ...current, description }));
    }, []);

    const reset = useCallback(() => {
        setInput(emptyCreateGameVersionInput);
        setErrors({});
    }, []);

    const submit = useCallback(() => {
        setProcessing(true);
        setErrors({});

        createGameVersion(workspace, game, input, {
            onSuccess: () => setInput(emptyCreateGameVersionInput),
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, input]);

    const current = useMemo(() => versions[0] ?? null, [versions]);

    return {
        versions,
        current,
        input,
        errors,
        processing,
        setName,
        setDescription,
        submit,
        reset,
    };
}
