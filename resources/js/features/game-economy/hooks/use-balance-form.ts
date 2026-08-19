import { useCallback, useState } from 'react';
import type { MutationOptions } from '../api';

export type FieldErrors<TInput> = Partial<
    Record<keyof TInput | string, string>
>;

export type UseBalanceFormResult<TInput> = {
    input: TInput;
    errors: FieldErrors<TInput>;
    processing: boolean;
    setField: <K extends keyof TInput>(field: K, value: TInput[K]) => void;
    submit: () => void;
    reset: () => void;
};

type UseBalanceFormOptions<TInput> = {
    /**
     * The shape the form starts in, and the shape it returns to after a successful submit.
     */
    initial: TInput;

    /**
     * The checks that run before a round trip. Return a message per field that is wrong, and nothing at all
     * when everything is fine.
     *
     * These exist to catch a malformed amount before a round trip; the server validates every field again
     * and its answer replaces whatever this decided.
     */
    validate?: (input: TInput) => FieldErrors<TInput>;

    /**
     * Perform the write. Always an Inertia visit — see `api/mutation`.
     */
    perform: (input: TInput, options: MutationOptions) => void;

    /**
     * Whether to clear the form after the server accepts it.
     *
     * On by default, because most forms in this module are "add another": somebody declaring an economy adds
     * eight resources in a row, and a form that kept the last one would have them deleting text before every
     * entry. Off for the edit forms, where the current values are the point.
     */
    resetOnSuccess?: boolean;

    onSuccess?: () => void;
};

/**
 * The form behaviour every write on the balance screens shares.
 *
 * There are a dozen forms in this feature — resources, flows, actions, costs, rewards, effects, variables,
 * scenarios, overrides, assumptions, observations, snapshots — and they differ only in their fields, their
 * checks and which call they make. A dozen hooks would be a dozen copies of the same four pieces of state
 * and the same submit sequence, and the twelfth would be the one where somebody forgot to clear the local
 * errors.
 *
 * That sequence is the part worth having once. A failed submit clears the client-side errors *before*
 * showing the server's, because displaying both at the same time would leave somebody reading two opinions
 * about the same field — and the server's is the one that decided.
 */
export function useBalanceForm<TInput extends Record<string, unknown>>({
    initial,
    validate,
    perform,
    resetOnSuccess = true,
    onSuccess,
}: UseBalanceFormOptions<TInput>): UseBalanceFormResult<TInput> {
    const [input, setInput] = useState<TInput>(initial);
    const [errors, setErrors] = useState<FieldErrors<TInput>>({});
    const [processing, setProcessing] = useState(false);

    const setField = useCallback(
        <K extends keyof TInput>(field: K, value: TInput[K]) => {
            setInput((current) => ({ ...current, [field]: value }));
        },
        [],
    );

    const reset = useCallback(() => {
        setInput(initial);
        setErrors({});
    }, [initial]);

    const submit = useCallback(() => {
        const local = validate?.(input) ?? {};

        if (Object.values(local).some((message) => message !== undefined)) {
            setErrors(local);

            return;
        }

        setProcessing(true);
        setErrors({});

        perform(input, {
            onSuccess: () => {
                if (resetOnSuccess) {
                    setInput(initial);
                }

                onSuccess?.();
            },
            onError: (serverErrors) =>
                setErrors(serverErrors as FieldErrors<TInput>),
            onFinish: () => setProcessing(false),
        });
    }, [input, initial, validate, perform, resetOnSuccess, onSuccess]);

    return { input, errors, processing, setField, submit, reset };
}
