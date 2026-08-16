import { useCallback, useState } from 'react';
import { createFeedback, deleteFeedback } from '../api';
import type { CreateFeedbackInput } from '../schemas/playtest';
import {
    emptyCreateFeedbackInput,
    validateFeedback,
} from '../schemas/playtest';
import type { Feedback } from '../types/playtest';

export type UseFeedbackResult = {
    feedback: Feedback[];
    input: CreateFeedbackInput;
    errors: Record<string, string | undefined>;
    processing: boolean;

    /**
     * The mean of the ratings that exist, or null when nobody gave one.
     *
     * Null rather than zero, for the same reason the server reports it that
     * way: somebody who commented without scoring did not score the game
     * lowest.
     */
    averageRating: number | null;

    setField: <K extends keyof CreateFeedbackInput>(
        field: K,
        value: CreateFeedbackInput[K],
    ) => void;
    submit: () => void;
    remove: (feedback: string) => void;
    reset: () => void;
};

/**
 * A session's feedback, and the form that records a piece of it.
 *
 * Kept separate from the observations throughout — see `useSessionTimeline`
 * for where the two are interleaved, and note that even there each entry stays
 * tagged with which kind it is.
 */
export function useFeedback(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    feedback: Feedback[],
): UseFeedbackResult {
    const [input, setInput] = useState<CreateFeedbackInput>(
        emptyCreateFeedbackInput,
    );
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const setField = useCallback(
        <K extends keyof CreateFeedbackInput>(
            field: K,
            value: CreateFeedbackInput[K],
        ) => {
            setInput((current) => ({ ...current, [field]: value }));
        },
        [],
    );

    const reset = useCallback(() => {
        setInput(emptyCreateFeedbackInput);
        setErrors({});
    }, []);

    const submit = useCallback(() => {
        const problem = validateFeedback(input.content);

        if (problem !== null) {
            setErrors({ content: problem });

            return;
        }

        setProcessing(true);
        setErrors({});

        createFeedback(workspace, game, playtest, session, input, {
            onSuccess: () => setInput(emptyCreateFeedbackInput),
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, playtest, session, input]);

    const remove = useCallback(
        (id: string) => {
            deleteFeedback(workspace, game, playtest, session, id);
        },
        [workspace, game, playtest, session],
    );

    const rated = feedback.filter((item) => item.rating !== null);

    const averageRating =
        rated.length === 0
            ? null
            : rated.reduce((total, item) => total + (item.rating ?? 0), 0) /
              rated.length;

    return {
        feedback,
        input,
        errors,
        processing,
        averageRating,
        setField,
        submit,
        remove,
        reset,
    };
}
