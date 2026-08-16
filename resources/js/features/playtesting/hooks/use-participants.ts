import { useCallback, useState } from 'react';
import { addParticipant, removeParticipant } from '../api';
import type { AddParticipantInput } from '../schemas/playtest';
import {
    emptyAddParticipantInput,
    validateDisplayName,
} from '../schemas/playtest';
import type { Participant } from '../types/playtest';

export type UseParticipantsResult = {
    participants: Participant[];

    /**
     * The people who actually played, as opposed to everybody in the room.
     * "Four players" and "six people present" are different facts about the
     * same evening.
     */
    players: Participant[];

    input: AddParticipantInput;
    errors: Record<string, string | undefined>;
    processing: boolean;
    setField: <K extends keyof AddParticipantInput>(
        field: K,
        value: AddParticipantInput[K],
    ) => void;
    submit: () => void;
    remove: (participant: string) => void;
    reset: () => void;
};

/**
 * The people at a session, and the form that seats another one.
 *
 * The form resets fully after each success, because participants are added in
 * a burst as everybody sits down and the next name has nothing to do with the
 * last one.
 */
export function useParticipants(
    workspace: string,
    game: string,
    playtest: string,
    session: string,
    participants: Participant[],
): UseParticipantsResult {
    const [input, setInput] = useState<AddParticipantInput>(
        emptyAddParticipantInput,
    );
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const setField = useCallback(
        <K extends keyof AddParticipantInput>(
            field: K,
            value: AddParticipantInput[K],
        ) => {
            setInput((current) => ({ ...current, [field]: value }));
        },
        [],
    );

    const reset = useCallback(() => {
        setInput(emptyAddParticipantInput);
        setErrors({});
    }, []);

    const submit = useCallback(() => {
        const problem = validateDisplayName(input.display_name);

        if (problem !== null) {
            setErrors({ display_name: problem });

            return;
        }

        setProcessing(true);
        setErrors({});

        addParticipant(workspace, game, playtest, session, input, {
            onSuccess: () => setInput(emptyAddParticipantInput),
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, playtest, session, input]);

    const remove = useCallback(
        (participant: string) => {
            removeParticipant(workspace, game, playtest, session, participant);
        },
        [workspace, game, playtest, session],
    );

    return {
        participants,
        players: participants.filter(
            (participant) => participant.role === 'player',
        ),
        input,
        errors,
        processing,
        setField,
        submit,
        remove,
        reset,
    };
}
