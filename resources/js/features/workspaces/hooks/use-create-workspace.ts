import { useCallback, useMemo, useState } from 'react';
import { createWorkspace } from '../api';
import type { CreateWorkspaceInput } from '../schemas/workspace';
import {
    emptyCreateWorkspaceInput,
    suggestWorkspaceSlug,
    validateWorkspaceName,
    validateWorkspaceSlug,
} from '../schemas/workspace';

export type UseCreateWorkspaceResult = {
    input: CreateWorkspaceInput;
    errors: Partial<Record<keyof CreateWorkspaceInput, string>>;
    processing: boolean;
    isValid: boolean;
    setName: (name: string) => void;
    setSlug: (slug: string) => void;
    setDescription: (description: string) => void;
    submit: () => void;
};

/**
 * Drives the create-workspace form.
 *
 * The address tracks the name until somebody edits it, and stops tracking for
 * good after that — silently rewriting an address the user chose would be a
 * worse surprise than an ugly one they picked.
 *
 * The checks here are for immediate feedback. The server validates the same
 * fields again, and its errors replace these on submit.
 */
export function useCreateWorkspace(): UseCreateWorkspaceResult {
    const [input, setInput] = useState<CreateWorkspaceInput>(
        emptyCreateWorkspaceInput,
    );
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
                slug: slugWasEdited ? current.slug : suggestWorkspaceSlug(name),
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

    const localErrors = useMemo(() => {
        const errors: Partial<Record<keyof CreateWorkspaceInput, string>> = {};

        if (input.name !== '') {
            const nameError = validateWorkspaceName(input.name);

            if (nameError) {
                errors.name = nameError;
            }
        }

        const slugError = validateWorkspaceSlug(input.slug);

        if (slugError) {
            errors.slug = slugError;
        }

        return errors;
    }, [input.name, input.slug]);

    const submit = useCallback(() => {
        setProcessing(true);
        setServerErrors({});

        createWorkspace(input, {
            onError: setServerErrors,
            onFinish: () => setProcessing(false),
        });
    }, [input]);

    return {
        input,
        errors: { ...localErrors, ...serverErrors },
        processing,
        isValid:
            input.name.trim() !== '' && Object.keys(localErrors).length === 0,
        setName,
        setSlug,
        setDescription,
        submit,
    };
}
