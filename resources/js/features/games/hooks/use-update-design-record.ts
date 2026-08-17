import { useCallback, useMemo, useState } from 'react';
import { updateDesignRecord } from '../api/update-design-record';
import type { DesignRecordInput } from '../schemas/design-record';
import {
    emptyDesignRecordInput,
    validateDesignRecord,
} from '../schemas/design-record';
import type { DesignRecord } from '../types/design-record';

export type UseUpdateDesignRecordResult = {
    input: DesignRecordInput;
    errors: Partial<Record<keyof DesignRecordInput, string>>;
    processing: boolean;
    isDirty: boolean;
    isValid: boolean;
    setField: <K extends keyof DesignRecordInput>(
        field: K,
        value: DesignRecordInput[K],
    ) => void;
    submit: () => void;
};

/**
 * Drives the design record form.
 *
 * One `setField` rather than thirteen setters. The other forms in this feature
 * have one per field and that reads well at three; at thirteen it is four
 * hundred lines of identical closures, and the field names are already a
 * compile-time union so nothing is given up by keying on them.
 *
 * Numbers stay strings for as long as the form is open, and nulls arrive as
 * empty strings — an input cannot hold null, and a controlled input given
 * `undefined` silently becomes uncontrolled. The conversion happens once, in
 * `updateDesignRecord`.
 */
export function useUpdateDesignRecord(
    workspace: string,
    game: string,
    record: DesignRecord | null,
): UseUpdateDesignRecordResult {
    const initial = useMemo(() => fromRecord(record), [record]);

    const [input, setInput] = useState<DesignRecordInput>(initial);
    const [serverErrors, setServerErrors] = useState<
        Record<string, string | undefined>
    >({});
    const [processing, setProcessing] = useState(false);

    const setField = useCallback(
        <K extends keyof DesignRecordInput>(
            field: K,
            value: DesignRecordInput[K],
        ) => {
            setInput((current) => ({ ...current, [field]: value }));
        },
        [],
    );

    const localErrors = useMemo(() => validateDesignRecord(input), [input]);

    const isDirty = useMemo(() => !sameInput(input, initial), [input, initial]);

    const submit = useCallback(() => {
        setProcessing(true);
        setServerErrors({});

        updateDesignRecord(workspace, game, input, {
            onError: setServerErrors,
            onFinish: () => setProcessing(false),
        });
    }, [workspace, game, input]);

    return {
        input,
        errors: { ...localErrors, ...serverErrors },
        processing,
        isDirty,
        isValid: Object.keys(localErrors).length === 0,
        setField,
        submit,
    };
}

/**
 * Turn the record the server sent into form state.
 *
 * A game with nothing decided has no record at all, which is the same starting
 * point as an empty form — so null and "all fields blank" collapse here, and
 * only here.
 */
function fromRecord(record: DesignRecord | null): DesignRecordInput {
    if (record === null) {
        return emptyDesignRecordInput();
    }

    return {
        pitch: record.pitch ?? '',
        player_count_min: numberToField(record.player_count_min),
        player_count_max: numberToField(record.player_count_max),
        play_time_min: numberToField(record.play_time_min),
        play_time_max: numberToField(record.play_time_max),
        target_age_min: numberToField(record.target_age_min),
        complexity: record.complexity ?? '',
        audience: record.audience ?? '',
        core_action: record.core_action ?? '',
        core_cost: record.core_cost ?? '',
        core_reward: record.core_reward ?? '',
        win_condition: record.win_condition ?? '',
        failure_condition: record.failure_condition ?? '',
        mechanics: (record.mechanics ?? []).map((mechanic) => mechanic.id),
    };
}

function numberToField(value: number | null): string {
    return value === null ? '' : String(value);
}

/**
 * Compare two form states.
 *
 * The mechanics are compared as sets rather than as arrays, because the order
 * they were clicked in is not a change to the design.
 */
function sameInput(a: DesignRecordInput, b: DesignRecordInput): boolean {
    const keys = Object.keys(a) as (keyof DesignRecordInput)[];

    return keys.every((key) => {
        if (key === 'mechanics') {
            return (
                a.mechanics.length === b.mechanics.length &&
                [...a.mechanics].sort().join() ===
                    [...b.mechanics].sort().join()
            );
        }

        return a[key] === b[key];
    });
}
