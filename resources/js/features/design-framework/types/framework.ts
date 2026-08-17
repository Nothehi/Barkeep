/**
 * The design framework shapes the server sends.
 *
 * Mirrors the resources under
 * `Modules\DesignFramework\Presentation\Http\Resources`. Those are the
 * authoritative shape — when one changes, change it here too.
 *
 * Only a top-level prop is wrapped in `data`; a resource nested inside another
 * arrives as a plain object, and a nested collection as a plain array. That is
 * why `latest_version` below is a version rather than `{ data: version }`, and
 * why every nested relation is optional: `whenLoaded` omits the key entirely
 * when the relation was not fetched, which is not the same as it being null.
 *
 * The split that runs through this whole file is the module's central one:
 * framework content (a criterion, a practice, a prompt) belongs to an edition
 * and is the same for everybody following it, while a studio's own record (an
 * evaluation, a completion, an answer) belongs to that game's adoption. They
 * arrive in separate collections and are joined by id on screen, which is what
 * stops a criterion from ever carrying somebody's grade.
 */

/**
 * Where a methodology, or one of its editions, is in its life.
 */
export type FrameworkStatus = 'draft' | 'published' | 'archived';

/**
 * Where one piece of content inside an edition is in its life.
 *
 * Draft content is visible to the people writing the methodology and to
 * nobody else. Archived content has been dropped from it, and stays readable
 * to the games that already recorded work against it.
 */
export type FrameworkContentStatus = 'draft' | 'published' | 'archived';

/**
 * Where a game's relationship with a methodology stands.
 *
 * Independent of both the framework's lifecycle and the game's. A published
 * framework, an active game and a paused adoption are all consistent at once:
 * the studio has decided to stop working the process for a while.
 */
export type GameFrameworkStatus = 'active' | 'paused' | 'completed';

/**
 * How well a game currently meets one criterion.
 *
 * `not_evaluated` is an absence rather than a bad score, and the server never
 * accepts it as a grade — clearing an assessment is not the same act as
 * making one.
 */
export type CriterionRating =
    'not_evaluated' | 'weak' | 'needs_work' | 'good' | 'strong';

/**
 * Whether a game has met one checklist requirement. Binary, and it stays that
 * way: a checklist whose items have states is a workflow engine.
 */
export type ChecklistItemState = 'incomplete' | 'complete';

/**
 * A lifecycle move the server is offering, already worded.
 *
 * The wording depends on both ends — reaching active from paused is resuming,
 * not "making active" — so the label is never derived on the client.
 */
export type FrameworkTransition = {
    status: string;
    label: string;
};

/**
 * What the signed in account may do with a methodology.
 *
 * Computed from the policy server side, so this is the same answer the request
 * would get. It decides what the interface offers, never what the server
 * allows.
 */
export type FrameworkPermissions = {
    canView: boolean;
    canUpdate: boolean;
    canPublish: boolean;
    canArchive: boolean;
    canCreateVersion: boolean;
};

/**
 * What the signed in account may do with one edition.
 *
 * `canUpdate` is false for a published edition even for an administrator, and
 * that is the whole builder in one flag: publishing freezes the content.
 */
export type FrameworkVersionPermissions = {
    canView: boolean;
    canUpdate: boolean;
    canPublish: boolean;
    canArchive: boolean;
};

/**
 * What the signed in account may do with a game's adoption.
 *
 * `canRecordProgress` covers evaluations, completions, ticks and answers
 * together, because the policy grants them together — the game must be open
 * and the adoption must be active.
 */
export type GameFrameworkPermissions = {
    canView: boolean;
    canRecordProgress: boolean;
    canPause: boolean;
    canResume: boolean;
    canComplete: boolean;
};

/**
 * One edition of a methodology.
 */
export type FrameworkVersion = {
    id: string;
    framework_id: string;
    version_number: number;
    label: string;
    name: string | null;
    description: string | null;
    status: FrameworkStatus;
    status_label: string;
    is_editable: boolean;
    is_adoptable: boolean;
    published_at: string | null;
    phases_count?: number;
    adoptions_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: FrameworkVersionPermissions;
    available_transitions: FrameworkTransition[];
};

/**
 * A methodology.
 *
 * The latest edition travels with it, because a framework card is unreadable
 * without one — a name alone tells a designer nothing about whether there is
 * anything to adopt.
 */
export type Framework = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    status: FrameworkStatus;
    status_label: string;
    latest_version?: FrameworkVersion | null;
    versions_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: FrameworkPermissions;
    available_transitions: FrameworkTransition[];
};

/**
 * One stage of a methodology.
 *
 * Named rather than titled, matching the domain: a phase is named ("Core
 * loop") while the content filed under it is titled ("Does the core loop
 * work?").
 */
export type DesignPhase = {
    id: string;
    framework_version_id: string;
    name: string;
    slug: string;
    description: string | null;
    position: number;
    status: FrameworkContentStatus;
    status_label: string;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * The fields every piece of phase content shares.
 *
 * `phase_id` is nullable throughout: content filed under no phase applies
 * across the whole methodology and reads as a preamble to the stages.
 */
type PhaseContent = {
    id: string;
    framework_version_id: string;
    phase_id: string | null;
    title: string;
    slug: string;
    position: number;
    status: FrameworkContentStatus;
    status_label: string;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A design rule to hold in mind. There is nothing to do with one, which is
 * why nothing about it counts towards progress.
 */
export type DesignPrinciple = PhaseContent & {
    description: string | null;
};

/**
 * A question the methodology asks of the design.
 */
export type DesignCriterion = PhaseContent & {
    description: string | null;
};

/**
 * An activity the methodology asks the studio to carry out.
 */
export type DesignPractice = PhaseContent & {
    description: string | null;
    instructions: string | null;
};

/**
 * An open question the methodology asks the studio to answer in prose.
 *
 * Counted and reported, deliberately excluded from the progress total: a
 * prompt has no right answer, so letting it move a percentage would reward
 * typing over thinking.
 */
export type DesignPrompt = PhaseContent & {
    prompt: string;
};

/**
 * One requirement on a checklist.
 *
 * Optional items are shown and tickable and do not count, which is what lets
 * an author add a nice-to-have without everybody's numbers moving.
 */
export type ChecklistItem = {
    id: string;
    checklist_id: string;
    title: string;
    slug: string;
    description: string | null;
    position: number;
    required: boolean;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A list of requirements, read as a unit.
 */
export type Checklist = PhaseContent & {
    description: string | null;
    items?: ChecklistItem[];
    items_count?: number;
};

/**
 * How much of one countable set has been dealt with.
 *
 * Counted on read, never stored — a stored percentage is a fourth fact that
 * can disagree with the three it came from.
 */
export type ProgressRatio = {
    completed: number;
    total: number;
    percentage: number;
    is_complete: boolean;
};

/**
 * How far a game has got through one phase.
 */
export type PhaseProgress = {
    phase_id: string;
    slug: string;
    name: string;
    position: number;
    criteria: ProgressRatio;
    practices: ProgressRatio;
    checklist_items: ProgressRatio;
    prompts: ProgressRatio;
    overall: ProgressRatio;
    percentage: number;
    is_complete: boolean;
    is_empty: boolean;
};

/**
 * How far a game has got through its methodology.
 *
 * `overall` counts evaluated criteria, completed practices and ticked
 * required checklist items. Prompts are reported beside it and excluded from
 * it on purpose.
 */
export type FrameworkProgress = {
    game_framework_id: string;
    framework_version_id: string;
    phases: ProgressRatio;
    criteria: ProgressRatio;
    practices: ProgressRatio;
    checklist_items: ProgressRatio;
    prompts: ProgressRatio;
    overall: ProgressRatio;
    percentage: number;
    is_complete: boolean;
    phase_progress: PhaseProgress[];
};

/**
 * A game's adoption of one edition of one methodology.
 *
 * The edition is captured at adoption and never changes: when v2 is
 * published, a game on v1 stays on v1, and its evaluations keep pointing at
 * the criteria those phases actually asked.
 */
export type GameFramework = {
    id: string;
    game_id: string;
    framework_version_id: string;
    version?: FrameworkVersion | null;
    framework?: Framework | null;
    status: GameFrameworkStatus;
    status_label: string;
    accepts_progress: boolean;
    started_at: string;
    completed_at: string | null;
    adopted_by: string;
    created_at: string | null;
    updated_at: string | null;
    permissions: GameFrameworkPermissions;
    available_transitions: FrameworkTransition[];
};

/**
 * A game's standing answer to one criterion.
 */
export type CriterionEvaluation = {
    id: string;
    game_framework_id: string;
    criterion_id: string;
    status: CriterionRating;
    status_label: string;
    is_evaluated: boolean;
    is_satisfactory: boolean;
    notes: string | null;
    evaluated_by: string;
    evaluated_at: string;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A practice this game has carried out. The row's existence is the fact, so
 * unticking removes it rather than storing a false flag.
 */
export type PracticeCompletion = {
    id: string;
    game_framework_id: string;
    practice_id: string;
    notes: string | null;
    completed_by: string;
    completed_at: string;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * A game's answer to one prompt.
 *
 * `was_revised` distinguishes a rewrite from a first answer, which is the
 * part a client could not work out for itself — answering again overwrites,
 * and no history is kept.
 */
export type PromptResponse = {
    id: string;
    game_framework_id: string;
    prompt_id: string;
    response: string;
    was_revised: boolean;
    answered_by: string;
    answered_at: string;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * Whether one item on a checklist has been ticked by this game.
 */
export type ChecklistItemProgress = {
    checklist_item_id: string;
    state: ChecklistItemState;
    is_complete: boolean;
};

/**
 * A checklist and how much of it this game has ticked.
 *
 * Reported separately from phase progress because a checklist is read as a
 * unit — "2 of 4 completed" above the boxes — and because the client needs a
 * tick against each item rather than just a total.
 */
export type ChecklistProgress = {
    checklist: Checklist;
    required: ProgressRatio;
    all: ProgressRatio;
    is_satisfied: boolean;
    items: ChecklistItemProgress[];
};

/**
 * The filters the framework catalogue accepts.
 */
export type FrameworkFilters = {
    search: string | null;
    status: FrameworkStatus | null;
};

/**
 * The choices the framework screens offer, worded by the server so the labels
 * and the sets have one definition.
 */
export type FrameworkOptions = {
    statuses: { value: FrameworkStatus; label: string }[];
};

/**
 * The grades a designer may actually choose, with what each one claims.
 *
 * The description is sent because the difference between "weak" and "needs
 * work" is not self-evident, and a designer guessing at it produces noise.
 */
export type RatingOption = {
    value: CriterionRating;
    label: string;
    description: string;
};
