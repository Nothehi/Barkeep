/**
 * The prototype and iteration shapes the server sends.
 *
 * Mirrors the resources under `Modules\PrototypeIteration\Presentation\Http\Resources`. Those are the
 * authoritative shape — when one changes, change it here too.
 *
 * Two things in here are worth reading before using them. Every `*_label` is already worded by an enum in
 * the domain, so the client renders what it is given rather than keeping a second copy of the vocabulary
 * that would drift the first time a category was renamed. And every `permissions` map is the policy's own
 * answer, which decides what the interface *offers* and never what the server allows — each ability is
 * checked again on the request that performs the action.
 */

import type { User } from '@/features/auth';
import type { GameVersion } from '@/features/games';

/**
 * What a prototype physically is. The distinction that matters is how it gets in front of players, because
 * that decides what an iteration costs.
 */
export type PrototypeType =
    'paper' | 'digital' | 'physical' | 'hybrid' | 'other';

/**
 * Where a prototype is in its own life. Archived is terminal — a studio picking the approach back up
 * creates a new prototype.
 */
export type PrototypeStatus = 'draft' | 'active' | 'archived';

/**
 * What kind of file an artifact is. Coarse on purpose: this answers "what am I looking at in this list?",
 * which a mime type answers worse.
 */
export type ArtifactType =
    'image' | 'pdf' | 'document' | 'spreadsheet' | 'model' | 'build' | 'other';

/**
 * Where a design cycle is in its own life.
 *
 * Independent of the playtests inside it: a playtest can finish while the cycle around it is still being
 * argued about. Both endings are terminal, because an iteration's outcome is the sentence the next
 * iteration is built on.
 */
export type IterationStatus =
    'planned' | 'in_progress' | 'completed' | 'cancelled';

/**
 * What a cycle turned out to be worth. `inconclusive` is a first-class answer rather than a failure.
 */
export type IterationOutcome =
    'success' | 'partial' | 'failed' | 'inconclusive';

/**
 * What part of the design a change touched. Deliberately not the same list as Playtesting's observation
 * categories: an observation is filed by what somebody noticed, a change by what the designer edited.
 */
export type ChangeCategory =
    | 'rules'
    | 'mechanics'
    | 'balance'
    | 'components'
    | 'player_interaction'
    | 'pacing'
    | 'ux'
    | 'theme'
    | 'economy'
    | 'other';

export type ExperimentStatus =
    'planned' | 'running' | 'completed' | 'cancelled';

/**
 * Where a decision stands. Accepted and rejected are terminal: a change of mind is a new decision in a
 * later cycle, never an edit to this one.
 */
export type DecisionStatus = 'proposed' | 'accepted' | 'rejected' | 'deferred';

/**
 * What kind of thing a citation points at. A note is the exception — it *is* the evidence rather than a
 * pointer to it, and carries no reference.
 */
export type EvidenceType =
    'playtest' | 'observation' | 'feedback' | 'experiment' | 'note';

/**
 * What sort of thing an entry on an iteration's timeline is.
 *
 * The lifecycle kinds are on the line rather than around it, because "the decision came four days after
 * the playtest and two hours before the cycle closed" is only visible when all of it shares one axis.
 */
export type TimelineEntryKind =
    | 'started'
    | 'change'
    | 'experiment'
    | 'playtest'
    | 'decision'
    | 'completed'
    | 'cancelled';

/**
 * One lifecycle move a record can currently make, already worded.
 *
 * The transition matrix lives in the domain and this list is derived from it per record, so the interface
 * renders the moves it is given rather than keeping a second copy of the rules.
 */
export type Transition<TStatus> = {
    status: TStatus;
    label: string;
};

/**
 * An option offered by a picker, worded by the server.
 */
export type VocabularyOption<TValue extends string> = {
    value: TValue;
    label: string;
};

export type DescribedOption<TValue extends string> =
    VocabularyOption<TValue> & {
        description: string;
    };

/**
 * What the signed in account may do with a prototype.
 */
export type PrototypePermissions = {
    canView: boolean;
    canUpdate: boolean;
    canArchive: boolean;
    canCreateVersion: boolean;
};

/**
 * What the signed in account may do with a design cycle.
 *
 * `canRecordWork` is the one nearly every control on the iteration screen is gated on: "may design work be
 * added to this cycle?" is a single question with a single answer, and giving the interface one boolean
 * rather than eight is what stops the two from drifting apart control by control.
 *
 * `canCreateGameVersion` is the inverse of the others — it is true only once the cycle has *closed*.
 */
export type IterationPermissions = {
    canView: boolean;
    canUpdate: boolean;
    canStart: boolean;
    canComplete: boolean;
    canCancel: boolean;
    canRecordWork: boolean;
    canAttachPlaytest: boolean;
    canCreateGameVersion: boolean;
};

export type Prototype = {
    id: string;
    game_id: string;
    game_version_id: string;
    name: string;
    description: string | null;
    type: PrototypeType;
    type_label: string;
    status: PrototypeStatus;
    status_label: string;
    created_by: string;
    creator?: User;
    version?: GameVersion;
    versions_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: PrototypePermissions;
    available_transitions: Transition<PrototypeStatus>[];
};

/**
 * A prototype as it appears in a list.
 *
 * Smaller than {@link Prototype} on purpose: cards offer no lifecycle actions, so the server does not
 * compute permissions or transitions for them, and the design version is flattened to its label.
 */
export type PrototypeCard = {
    id: string;
    game_id: string;
    game_version_id: string;
    name: string;
    description: string | null;
    type: PrototypeType;
    type_label: string;
    status: PrototypeStatus;
    status_label: string;
    version_label?: string | null;
    versions_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * One concrete state of a prototype: the cards as they were printed that week.
 *
 * `iterations_count` carries the immutability rule to the interface. A version with any iterations against
 * it is frozen, and the count is what lets the screen say so — and say how much history is at stake —
 * rather than offering an edit form that will be refused.
 */
export type PrototypeVersion = {
    id: string;
    prototype_id: string;
    version_number: number;
    label: string;
    name: string | null;
    description: string | null;
    prototype_name?: string | null;
    created_by: string;
    creator?: User;
    artifacts_count?: number;
    iterations_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A file attached to one state of a prototype.
 *
 * There is deliberately no URL here. Artifacts are private, so the interface builds a download address
 * from the ids and that route authorizes before it streams a byte.
 */
export type PrototypeArtifact = {
    id: string;
    prototype_version_id: string;
    name: string;
    type: ArtifactType;
    type_label: string;
    size: number | null;
    size_label: string | null;
    mime_type: string | null;
    original_filename: string | null;
    created_by: string;
    creator?: User;
    created_at: string | null;
};

export type Iteration = {
    id: string;
    game_id: string;
    game_version_id: string;
    prototype_version_id: string;
    title: string;
    objective: string;
    hypothesis: string | null;
    status: IterationStatus;
    status_label: string;
    outcome: IterationOutcome | null;
    outcome_label: string | null;
    summary: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_by: string;
    creator?: User;
    version?: GameVersion;
    prototype_version?: PrototypeVersion;
    changes_count?: number;
    experiments_count?: number;
    decisions_count?: number;
    playtests_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: IterationPermissions;
    available_transitions: Transition<IterationStatus>[];
};

/**
 * A design cycle as it appears in a list.
 *
 * The objective is here and the hypothesis is not, which is the opposite of the choice a playtest card
 * makes: an iterations list is scanned for "what were we trying to fix?", and that is the objective.
 */
export type IterationCard = {
    id: string;
    game_id: string;
    game_version_id: string;
    prototype_version_id: string;
    title: string;
    objective: string;
    status: IterationStatus;
    status_label: string;
    outcome: IterationOutcome | null;
    outcome_label: string | null;
    version_label?: string | null;
    prototype_version_label?: string | null;
    prototype_name?: string | null;
    changes_count?: number;
    experiments_count?: number;
    decisions_count?: number;
    playtests_count?: number;
    started_at: string | null;
    completed_at: string | null;
    created_at: string | null;
};

export type DesignChange = {
    id: string;
    iteration_id: string;
    category: ChangeCategory;
    category_label: string;
    title: string;
    description: string | null;
    reason: string;
    created_by: string;
    creator?: User;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A focused attempt to answer one design question.
 *
 * The two halves arrive as two halves, in the order they were written: the question, hypothesis, method and
 * expectation before it ran, the result and conclusion after. A `conclusion` of null against a populated
 * `actual_result` is a real state — the studio has seen what happened and not yet decided what it means.
 */
export type DesignExperiment = {
    id: string;
    iteration_id: string;
    title: string;
    question: string;
    hypothesis: string | null;
    method: string | null;
    expected_result: string | null;
    actual_result: string | null;
    conclusion: string | null;
    status: ExperimentStatus;
    status_label: string;
    started_at: string | null;
    completed_at: string | null;
    created_by: string;
    creator?: User;
    created_at: string | null;
    updated_at: string | null;
    available_transitions: Transition<ExperimentStatus>[];
};

export type DesignDecision = {
    id: string;
    iteration_id: string;
    title: string;
    decision: string;
    reason: string;
    status: DecisionStatus;
    status_label: string;
    is_settled: boolean;
    decided_by: string | null;
    decider?: User;
    decided_at: string | null;
    created_by: string;
    creator?: User;
    evidence_count?: number;
    created_at: string | null;
    updated_at: string | null;
    available_transitions: Transition<DecisionStatus>[];
};

/**
 * One citation, resolved.
 *
 * `excerpt` is the cited record's own words, read live from the context that owns it at the moment of the
 * request — so a correction to an observation appears in every decision that cited it. `description` is the
 * studio's reason for citing it, and is never a copy of the words.
 *
 * `is_resolved` false is a first-class state, not an error: the commonest cause is permission rather than
 * deletion, and rendering it as a citation you cannot see beats a shorter list that reads as "nothing
 * supported this".
 */
export type CitedEvidence = {
    id: string;
    type: EvidenceType;
    type_label: string;
    reference_id: string | null;
    description: string | null;
    excerpt: string | null;
    attribution: string | null;
    playtest_id: string | null;
    is_resolved: boolean;
    is_linkable: boolean;
};

/**
 * A playtest a cycle was tested through.
 *
 * Every figure was read from Playtesting at the moment of the request, so these are the same counts the
 * playtest's own screen shows. `link_id` addresses the association — which is what detaching uses, so the
 * request never names the playtest at all.
 */
export type PlaytestReference = {
    link_id: string;
    playtest_id: string;
    title: string;
    status: string;
    status_label: string;
    attached_at: string | null;
    sessions_count: number;
    participants_count: number;
    observations_count: number;
    feedback_count: number;
    duration_seconds: number | null;
    is_available: boolean;
    has_evidence: boolean;
};

/**
 * A playtest the attach picker can offer.
 */
export type SelectablePlaytest = {
    id: string;
    title: string;
    status: string;
    status_label: string;
};

/**
 * What a cycle produced, counted on read.
 *
 * The three pairs are the point: experiments against completed ones shows a cycle that closed with
 * questions still open, decisions against accepted ones shows a cycle that concluded nothing, and playtests
 * against observations shows evidence that was attached but never produced anything.
 */
export type IterationSummary = {
    iteration_id: string;
    status: IterationStatus;
    status_label: string;
    outcome: IterationOutcome | null;
    outcome_label: string | null;
    summary: string | null;
    objective: string;
    hypothesis: string | null;
    changes: number;
    experiments: number;
    completed_experiments: number;
    decisions: number;
    accepted_decisions: number;
    evidence: number;
    playtests: number;
    sessions: number;
    observations: number;
    feedback: number;
    has_work: boolean;
    has_evidence: boolean;
    experiments_settled: boolean;
};

/**
 * One thing that happened during a cycle.
 *
 * Flat and already worded, because the entries came from five kinds of record across four tables and one
 * other bounded context — a nested shape would make the client destructure a union to draw one list.
 *
 * `counts` is the exception to "already worded": this application pluralises on the client against the
 * shared catalogue, so a playtest entry hands over its numbers and lets the interface say them.
 */
export type TimelineEntry = {
    kind: TimelineEntryKind;
    kind_label: string;
    is_lifecycle: boolean;
    id: string;
    at: string | null;
    title: string;
    body: string | null;
    badge: string | null;
    status: string | null;
    reference: string | null;
    counts: Record<string, number> | null;
};

export type IterationTimeline = {
    iteration_id: string;
    is_empty: boolean;
    tally: Record<string, number>;
    entries: TimelineEntry[];
};

export type PrototypeFilters = {
    search: string | null;
    status: PrototypeStatus | null;
    type: PrototypeType | null;
};

export type IterationFilters = {
    search: string | null;
    status: IterationStatus | null;
    outcome: IterationOutcome | null;
    prototype: string | null;
};

/**
 * The vocabulary the prototype screens choose from, worded by the server.
 */
export type PrototypeOptions = {
    types: DescribedOption<PrototypeType>[];
    statuses: VocabularyOption<PrototypeStatus>[];
    artifact_types: DescribedOption<ArtifactType>[];
};

/**
 * The vocabulary the iteration screens choose from, worded by the server.
 *
 * `requires_reference` travels with each evidence type so the citation form knows whether to show a picker
 * without keeping its own copy of which types point at something.
 */
export type IterationOptions = {
    statuses: VocabularyOption<IterationStatus>[];
    outcomes: DescribedOption<IterationOutcome>[];
    change_categories: DescribedOption<ChangeCategory>[];
    experiment_statuses: VocabularyOption<ExperimentStatus>[];
    decision_statuses: VocabularyOption<DecisionStatus>[];
    evidence_types: (VocabularyOption<EvidenceType> & {
        requires_reference: boolean;
    })[];
};
