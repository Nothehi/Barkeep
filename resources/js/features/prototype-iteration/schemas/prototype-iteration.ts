/**
 * Client-side shapes and checks for prototype and iteration forms.
 *
 * These mirror `Modules\PrototypeIteration\Application\Validation\IterationValidationRules` and the form
 * requests beside it. They exist to give immediate feedback while somebody types; the server validates every
 * field again and its answer wins.
 *
 * The numbers are duplicated rather than fetched, which is a deliberate small cost: a limit that arrives over
 * the wire cannot be checked before the first keystroke, and being told "too long" after submitting is
 * exactly the experience these checks exist to avoid.
 *
 * Most floors here are low, because most of this is typed in a hurry. Three are not, and each guards a field
 * somebody will read in a year and have to understand without asking anybody: an iteration's objective, a
 * change's reason, and a decision's reason.
 */

import type {
    ArtifactType,
    ChangeCategory,
    EvidenceType,
    IterationOutcome,
    IterationStatus,
    PrototypeStatus,
    PrototypeType,
} from '../types/prototype-iteration';

export const PROTOTYPE_NAME_MIN_LENGTH = 2;
export const PROTOTYPE_NAME_MAX_LENGTH = 160;
export const PROTOTYPE_DESCRIPTION_MAX_LENGTH = 5000;

export const PROTOTYPE_VERSION_NAME_MAX_LENGTH = 160;
export const PROTOTYPE_VERSION_DESCRIPTION_MAX_LENGTH = 5000;

export const ARTIFACT_NAME_MAX_LENGTH = 255;

/**
 * Generous on purpose: a print-ready card sheet at 300dpi genuinely runs to tens of megabytes, and a limit
 * that refused one would make the whole feature useless for the commonest case.
 */
export const ARTIFACT_MAX_KILOBYTES = 51200;

export const ITERATION_TITLE_MIN_LENGTH = 3;
export const ITERATION_TITLE_MAX_LENGTH = 160;

/**
 * "Improve the game" is not an objective, and a cycle whose purpose nobody wrote down is one nobody can
 * interpret when they come back to it.
 */
export const ITERATION_OBJECTIVE_MIN_LENGTH = 10;
export const ITERATION_OBJECTIVE_MAX_LENGTH = 2000;
export const ITERATION_HYPOTHESIS_MAX_LENGTH = 2000;

/**
 * The outcome is the index and the summary is the account. "Partial" on its own tells the next designer where
 * they are but not where to start.
 */
export const ITERATION_SUMMARY_MIN_LENGTH = 10;
export const ITERATION_SUMMARY_MAX_LENGTH = 5000;

export const CHANGE_TITLE_MIN_LENGTH = 3;
export const CHANGE_TITLE_MAX_LENGTH = 200;
export const CHANGE_DESCRIPTION_MAX_LENGTH = 5000;
export const CHANGE_REASON_MIN_LENGTH = 5;
export const CHANGE_REASON_MAX_LENGTH = 5000;

export const EXPERIMENT_TITLE_MIN_LENGTH = 3;
export const EXPERIMENT_TITLE_MAX_LENGTH = 200;
export const EXPERIMENT_QUESTION_MIN_LENGTH = 5;
export const EXPERIMENT_QUESTION_MAX_LENGTH = 2000;
export const EXPERIMENT_PROSE_MAX_LENGTH = 2000;
export const EXPERIMENT_RESULT_MIN_LENGTH = 5;
export const EXPERIMENT_RESULT_MAX_LENGTH = 5000;

export const DECISION_TITLE_MIN_LENGTH = 3;
export const DECISION_TITLE_MAX_LENGTH = 200;
export const DECISION_STATEMENT_MIN_LENGTH = 5;
export const DECISION_STATEMENT_MAX_LENGTH = 5000;
export const DECISION_REASON_MIN_LENGTH = 5;
export const DECISION_REASON_MAX_LENGTH = 5000;

export const EVIDENCE_DESCRIPTION_MAX_LENGTH = 2000;

export type CreatePrototypeInput = {
    game_version_id: string;
    name: string;
    description: string;
    type: PrototypeType;
};

export type UpdatePrototypeInput = {
    name: string;
    description: string;
    type: PrototypeType;
};

export type CreatePrototypeVersionInput = {
    name: string;
    description: string;
};

export type CreateArtifactInput = {
    file: File | null;
    name: string;
    type: ArtifactType | '';
};

export type CreateIterationInput = {
    game_version_id: string;
    prototype_version_id: string;
    title: string;
    objective: string;
    hypothesis: string;
};

export type UpdateIterationInput = {
    game_version_id: string;
    prototype_version_id: string;
    title: string;
    objective: string;
    hypothesis: string;
};

export type CompleteIterationInput = {
    outcome: IterationOutcome | '';
    summary: string;
};

export type DesignChangeInput = {
    category: ChangeCategory;
    title: string;
    description: string;
    reason: string;
};

export type ExperimentInput = {
    title: string;
    question: string;
    hypothesis: string;
    method: string;
    expected_result: string;
};

export type CompleteExperimentInput = {
    actual_result: string;
    conclusion: string;
};

export type DecisionInput = {
    title: string;
    decision: string;
    reason: string;
};

export type EvidenceInput = {
    type: EvidenceType;
    reference_id: string;
    description: string;
};

export type NextGameVersionInput = {
    name: string;
    description: string;
};

/**
 * A new prototype has no design version chosen; the screen fills it in from the game's latest, which is what
 * a designer means by "the current design".
 */
export const emptyCreatePrototypeInput: CreatePrototypeInput = {
    game_version_id: '',
    name: '',
    description: '',
    type: 'paper',
};

/**
 * Both fields blank, and that is load-bearing rather than lazy: the immutability rule is only reasonable if
 * cutting the next version costs nothing.
 */
export const emptyCreatePrototypeVersionInput: CreatePrototypeVersionInput = {
    name: '',
    description: '',
};

export const emptyCreateArtifactInput: CreateArtifactInput = {
    file: null,
    name: '',
    type: '',
};

export const emptyCreateIterationInput: CreateIterationInput = {
    game_version_id: '',
    prototype_version_id: '',
    title: '',
    objective: '',
    hypothesis: '',
};

/**
 * No outcome preselected. An outcome that defaulted to something would record the platform's guess as the
 * studio's own judgement.
 */
export const emptyCompleteIterationInput: CompleteIterationInput = {
    outcome: '',
    summary: '',
};

export const emptyDesignChangeInput: DesignChangeInput = {
    category: 'other',
    title: '',
    description: '',
    reason: '',
};

export const emptyExperimentInput: ExperimentInput = {
    title: '',
    question: '',
    hypothesis: '',
    method: '',
    expected_result: '',
};

export const emptyCompleteExperimentInput: CompleteExperimentInput = {
    actual_result: '',
    conclusion: '',
};

export const emptyDecisionInput: DecisionInput = {
    title: '',
    decision: '',
    reason: '',
};

export const emptyEvidenceInput: EvidenceInput = {
    type: 'note',
    reference_id: '',
    description: '',
};

export const emptyNextGameVersionInput: NextGameVersionInput = {
    name: '',
    description: '',
};

/**
 * Explain why a value is too short or too long, or return null when it is fine.
 *
 * One helper rather than a function per field, because the shape of the check never varies and the message
 * only differs by which thing is being described. The wording is passed in already translated.
 */
export function validateLength(
    value: string,
    {
        min,
        max,
        tooShort,
        tooLong,
    }: {
        min: number;
        max: number;
        tooShort: string;
        tooLong: string;
    },
): string | null {
    const trimmed = value.trim();

    if (trimmed.length < min) {
        return tooShort;
    }

    if (trimmed.length > max) {
        return tooLong;
    }

    return null;
}

/**
 * Determine whether an uploaded file is within the ceiling the server will accept.
 *
 * Checked before the upload starts, because the alternative is somebody watching a 200MB progress bar
 * complete and then being told no.
 */
export function isWithinArtifactSizeLimit(file: File): boolean {
    return file.size <= ARTIFACT_MAX_KILOBYTES * 1024;
}

/**
 * The statuses a prototypes list can be filtered by, plus "everything".
 *
 * An empty string rather than a null, because that is what an unset `<Select>` gives back and translating it
 * once here beats translating it at every call site.
 */
export type PrototypeStatusFilter = PrototypeStatus | '';
export type PrototypeTypeFilter = PrototypeType | '';
export type IterationStatusFilter = IterationStatus | '';
export type IterationOutcomeFilter = IterationOutcome | '';
