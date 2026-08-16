import { useCallback, useMemo, useState } from 'react';
import { createGame } from '../api';
import type { CreateGameInput } from '../schemas/game';
import {
    emptyCreateGameInput,
    suggestGameSlug,
    validateGameName,
    validateGameSlug,
} from '../schemas/game';
import type { DesignPhase } from '../types/game';

export type UseCreateGameResult = {
    input: CreateGameInput;
    errors: Partial<Record<keyof CreateGameInput, string>>;
    processing: boolean;
    isValid: boolean;
    setName: (name: string) => void;
    setSlug: (slug: string) => void;
    setDescription: (description: string) => void;
    setDesignPhase: (phase: DesignPhase) => void;
    reset: () => void;
    submit: () => void;
};

/**
 * Drives the create-game form.
 *
 * The address tracks the name until somebody edits it, and stops tracking for
 * good after that — silently rewriting an address the user chose would be a
 * worse surprise than an ugly one they picked.
 *
 * There is no status field. A game always starts as a draft; the phase is
 * offered because a designer often has a prototype in a drawer before they
 * write anything down.
 *
 * The checks here are for immediate feedback. The server validates the same
 * fields again, and its errors replace these on submit.
 */
export function useCreateGame(workspace: string): UseCreateGameResult {
    const [input, setInput] = useState<CreateGameInput>(emptyCreateGameInput);
    const [slugWasEdited, setSlugWasEdited] = useState(false);
    const [serverErrors, setServerErrors] = useState<
        Record<string, string | undefined>
    >({});
    const [processing, setProcessing] = useState(false);

    const setName = useCallback(
        (name: string) => {
            setInput((current) => ({
                ...current,
                name,
                slug: slugWasEdited ? current.slug : suggestGameSlug(name),
            }));
        },
        [slugWasEdited],
    );

    const setSlug = useCallback((slug: string) => {
        setSlugWasEdited(true);
        setInput((current) => ({ ...current, slug }));
    }, []);

    const setDescription = useCallback((description: string) => {
        setInput((current) => ({ ...current, description }));
    }, []);

    const setDesignPhase = useCallback((design_phase: DesignPhase) => {
        setInput((current) => ({ ...current, design_phase }));
    }, []);

    const reset = useCallback(() => {
        setInput(emptyCreateGameInput);
        setSlugWasEdited(false);
        setServerErrors({});
    }, []);

    const localErrors = useMemo(() => {
        const errors: Partial<Record<keyof CreateGameInput, string>> = {};

        if (input.name !== '') {
            const nameError = validateGameName(input.name);

            if (nameError) {
                errors.name = nameError;
            }
        }

        const slugError = validateGameSlug(input.slug);

        if (slugError) {
            errors.slug = slugError;
        }

        return errors;
    }, [input.name, input.slug]);

    const submit = useCallback(() => {
        setProcessing(true);
        setServerErrors({});

        createGame(workspace, input, {
            onError: setServerErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, input]);

    return {
        input,
        errors: { ...localErrors, ...serverErrors },
        processing,
        isValid:
            input.name.trim() !== '' && Object.keys(localErrors).length === 0,
        setName,
        setSlug,
        setDescription,
        setDesignPhase,
        reset,
        submit,
    };
}
