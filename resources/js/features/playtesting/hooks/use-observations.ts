import { useCallback, useState } from 'react';
import { createObservation, deleteObservation } from '../api';
import type { CreateObservationInput } from '../schemas/playtest';
import {
    emptyCreateObservationInput,
    validateObservation,
} from '../schemas/playtest';
import type { Observation } from '../types/playtest';

export type UseObservationsResult = {
    observations: Observation[];
    input: CreateObservationInput;
    errors: Record<string, string | undefined>;
    processing: boolean;
    setField: <K extends keyof CreateObservationInput>(
        field: K,
        value: CreateObservationInput[K],
    ) => void;
    submit: () => void;
    remove: (observation: string) => void;
    reset: () => void;
};

/**
 * A session's observations, and the form that adds one.
 *
 * The list arrives with the page rather than being fetched, so it is already
 * ordered and scoped, and a successful submit reloads it — which is what keeps
 * the timeline equal to what the server stored rather than to what this
 * component believes it sent.
 *
 * The form keeps the category between submissions and clears the text. A
 * designer recording four rules problems in a row should not have to re-pick
 * "rules" each time, and the text is the only field that is definitely
 * finished with.
 */
export function useObservations(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    observations: Observation[],
): UseObservationsResult {
    const [input, setInput] = useState<CreateObservationInput>(
        emptyCreateObservationInput,
    );
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const setField = useCallback(
        <K extends keyof CreateObservationInput>(
            field: K,
            value: CreateObservationInput[K],
        ) => {
            setInput((current) => ({ ...current, [field]: value }));
        },
        [],
    );

    const reset = useCallback(() => {
        setInput(emptyCreateObservationInput);
        setErrors({});
    }, []);

    const submit = useCallback(() => {
        const problem = validateObservation(input.content);

        if (problem !== null) {
            setErrors({ content: problem });

            return;
        }

        setProcessing(true);
        setErrors({});

        createObservation(workspace, game, playtest, session, input, {
            onSuccess: () =>
                setInput((current) => ({
                    ...current,
                    content: '',
                    participant_id: '',
                })),
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, playtest, session, input]);

    const remove = useCallback(
        (observation: string) => {
            deleteObservation(workspace, game, playtest, session, observation);
        },
        [workspace, game, playtest, session],
    );

    return {
        observations,
        input,
        errors,
        processing,
        setField,
        submit,
        remove,
        reset,
    };
}
