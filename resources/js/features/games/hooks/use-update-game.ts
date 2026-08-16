import { useCallback, useMemo, useState } from 'react';
import { updateGame } from '../api';
import type { UpdateGameInput } from '../schemas/game';
import { validateGameName, validateGameSlug } from '../schemas/game';
import type { Game } from '../types/game';

export type UseUpdateGameResult = {
    input: UpdateGameInput;
    errors: Partial<Record<keyof UpdateGameInput, string>>;
    processing: boolean;
    isDirty: boolean;
    isValid: boolean;
    setName: (name: string) => void;
    setSlug: (slug: string) => void;
    setDescription: (description: string) => void;
    submit: () => void;
};

/**
 * Drives the game settings form.
 *
 * Unlike creation the address does not track the name: the game already has
 * one, links to it exist, and moving it because somebody fixed a typo in the
 * title would break them. Renaming and re-addressing are separate decisions
 * here, and the field says so.
 */
export function useUpdateGame(
    workspace: string,
    game: Game,
): UseUpdateGameResult {
    const [input, setInput] = useState<UpdateGameInput>({
        name: game.name,
        slug: game.slug,
        description: game.description ?? '',
    });
    const [serverErrors, setServerErrors] = useState<
        Record<string, string | undefined>
    >({});
    const [processing, setProcessing] = useState(false);

    const setName = useCallback((name: string) => {
        setInput((current) => ({ ...current, name }));
    }, []);

    const setSlug = useCallback((slug: string) => {
        setInput((current) => ({ ...current, slug }));
    }, []);

    const setDescription = useCallback((description: string) => {
        setInput((current) => ({ ...current, description }));
    }, []);

    const localErrors = useMemo(() => {
        const errors: Partial<Record<keyof UpdateGameInput, string>> = {};

        const nameError = validateGameName(input.name);

        if (nameError) {
            errors.name = nameError;
        }

        const slugError =
            input.slug === ''
                ? 'The address is required.'
                : validateGameSlug(input.slug);

        if (slugError) {
            errors.slug = slugError;
        }

        return errors;
    }, [input.name, input.slug]);

    const isDirty = useMemo(
        () =>
            input.name !== game.name ||
            input.slug !== game.slug ||
            input.description !== (game.description ?? ''),
        [input, game],
    );

    const submit = useCallback(() => {
        setProcessing(true);
        setServerErrors({});

        updateGame(workspace, game.slug, input, {
            onError: setServerErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game.slug, input]);

    return {
        input,
        errors: { ...localErrors, ...serverErrors },
        processing,
        isDirty,
        isValid: Object.keys(localErrors).length === 0,
        setName,
        setSlug,
        setDescription,
        submit,
    };
}
