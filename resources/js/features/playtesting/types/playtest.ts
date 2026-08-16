/**
 * The playtesting shapes the server sends.
 *
 * Mirrors the resources under
 * `Modules\Playtesting\Presentation\Http\Resources`. Those are the
 * authoritative shape — when one changes, change it here too.
 */

import type { User } from '@/features/auth';
import type { GameVersion } from '@/features/games';

/**
 * Where an investigation is in its own life.
 *
 * Independent of {@link SessionStatus}: a playtest with four completed
 * sittings is still in progress until the designer says they have learned
 * enough.
 */
export type PlaytestStatus =
    'planned' | 'in_progress' | 'completed' | 'cancelled';

/**
 * Where one sitting is in its own life.
 */
export type SessionStatus =
    'planned' | 'in_progress' | 'completed' | 'cancelled';

/**
 * What somebody was doing at a session. Nothing here grants any permission.
 */
export type ParticipantRole =
    'player' | 'observer' | 'facilitator' | 'designer';

/**
 * What part of the design an observation is about.
 *
 * Deliberately short. This is the version that gets replaced when the
 * framework system arrives and owns a configurable taxonomy.
 */
export type ObservationCategory =
    | 'rules'
    | 'gameplay'
    | 'player_behavior'
    | 'balance'
    | 'ux'
    | 'pacing'
    | 'components'
    | 'other';

/**
 * What the signed in account may do with a playtest.
 *
 * Computed from the policy server side, so this is the same answer the request
 * would get. It decides what the interface offers, never what the server
 * allows — every one of these is checked again on the request that performs
 * the action.
 *
 * `canRecordConclusion` outlives `canUpdate` on purpose: a completed playtest
 * refuses every change to its plan and still accepts what it concluded.
 */
export type PlaytestPermissions = {
    canView: boolean;
    canUpdate: boolean;
    canRecordConclusion: boolean;
    canComplete: boolean;
    canCancel: boolean;
    canCreateSession: boolean;
};

/**
 * What the signed in account may do with a session.
 *
 * The evidence abilities come apart from the lifecycle ones: a session that
 * has ended still shows its participants and observations, and offers no way
 * to add any.
 */
export type SessionPermissions = {
    canView: boolean;
    canUpdate: boolean;
    canStart: boolean;
    canComplete: boolean;
    canCancel: boolean;
    canManageParticipants: boolean;
    canCreateObservation: boolean;
    canManageObservations: boolean;
    canCreateFeedback: boolean;
    canManageFeedback: boolean;
};

/**
 * One lifecycle move a playtest can currently make, already worded.
 *
 * The transition matrix lives in the domain and this list is derived from it
 * per playtest, so the interface renders the moves it is given rather than
 * keeping a second copy of the rules that would drift from the first.
 */
export type PlaytestTransition = {
    status: PlaytestStatus;
    label: string;
};

export type Playtest = {
    id: string;
    game_id: string;
    game_version_id: string;
    title: string;
    objective: string;
    hypothesis: string | null;
    conclusion: string | null;
    status: PlaytestStatus;
    status_label: string;
    planned_at: string | null;
    completed_at: string | null;
    created_by: string;
    creator?: User;
    version?: GameVersion;
    sessions_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: PlaytestPermissions;
    available_transitions: PlaytestTransition[];
};

/**
 * A playtest as it appears in a list.
 *
 * Smaller than {@link Playtest} on purpose: cards do not offer lifecycle
 * actions, so the server does not compute permissions or transitions for them.
 * The hypothesis is here and the objective is not — a list is scanned for
 * "what were we trying to find out?", and the hypothesis is the sharper
 * answer.
 */
export type PlaytestSummary = {
    id: string;
    game_id: string;
    game_version_id: string;
    title: string;
    hypothesis: string | null;
    status: PlaytestStatus;
    status_label: string;
    version_label: string | null;
    planned_at: string | null;
    sessions_count?: number;
    created_at: string | null;
    updated_at: string | null;
};

export type PlaytestSession = {
    id: string;
    playtest_id: string;
    status: SessionStatus;
    status_label: string;
    planned_at: string | null;
    started_at: string | null;
    ended_at: string | null;

    /**
     * Null while a session is running, which is different from zero — the
     * screen draws a live counter from `started_at` instead.
     */
    duration_seconds: number | null;
    duration_label: string | null;

    location: string | null;
    notes: string | null;
    outcome: string | null;
    created_by: string;
    creator?: User;
    participants_count?: number;
    observations_count?: number;
    feedback_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: SessionPermissions;
};

/**
 * Somebody who was at a session.
 *
 * `display_name` is always present and is what to show. Most participants have
 * no Barkeep account at all, so `user` is the exception rather than the name
 * being a fallback for a missing one.
 */
export type Participant = {
    id: string;
    session_id: string;
    user_id: string | null;
    user?: User;
    display_name: string;
    role: ParticipantRole;
    role_label: string;
    is_registered: boolean;
    joined_at: string | null;
    left_at: string | null;
    created_at: string | null;
};

/**
 * Something a designer noticed.
 *
 * Sort a timeline by `occurred_at`, which falls back to when the observation
 * was written down — an observation typed up after the session still belongs
 * in the account rather than dropping out of it on a null.
 */
export type Observation = {
    id: string;
    session_id: string;
    participant_id: string | null;
    participant?: Participant;
    category: ObservationCategory;
    category_label: string;
    content: string;
    observed_at: string | null;
    occurred_at: string | null;
    created_by: string;
    creator?: User;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * Something a participant said.
 *
 * `participant` is who said it, `creator` is who typed it in — usually the
 * facilitator, and never to be conflated with the speaker.
 */
export type Feedback = {
    id: string;
    session_id: string;
    participant_id: string | null;
    participant?: Participant;
    content: string;

    /**
     * Null means "did not put a number on it", which is not a low score.
     */
    rating: number | null;
    rating_label: string | null;
    rating_max: number;

    is_anonymous: boolean;
    created_by: string;
    creator?: User;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * What a playtest has produced, counted on read.
 *
 * Every null here is meaningful and none of them is a zero: a playtest with no
 * rated feedback has no average rating, and a playtest whose sessions never
 * ran has no average duration.
 */
export type PlaytestMetrics = {
    playtest_id: string;
    session_count: number;
    completed_session_count: number;
    cancelled_session_count: number;
    participant_count: number;
    player_count: number;
    observation_count: number;
    feedback_count: number;
    rated_feedback_count: number;
    average_feedback_rating: number | null;
    total_duration_seconds: number | null;
    total_duration_label: string | null;
    average_session_duration_seconds: number | null;
    average_session_duration_label: string | null;
    has_evidence: boolean;
};

/**
 * The filters currently applied to a playtests list, echoed back by the
 * server.
 */
export type PlaytestFilters = {
    search: string | null;
    status: PlaytestStatus | null;
};

/**
 * The values the playtesting screens let somebody choose between.
 *
 * Sent from the server so the labels, the ordering and the sets themselves
 * have one definition rather than a second one here that goes stale. The
 * observation categories in particular are the list most likely to change,
 * since the framework system will eventually own them.
 */
export type PlaytestOptions = {
    statuses: { value: PlaytestStatus; label: string }[];
    categories: {
        value: ObservationCategory;
        label: string;
        description: string;
    }[];
    roles: { value: ParticipantRole; label: string; description: string }[];
    rating_scale: number[];
};

/**
 * One thing that happened during a session, ready to draw in order.
 *
 * Assembled on the client from the observations and the feedback rather than
 * fetched as a merged list, because the distinction between the two has to
 * survive as far as the reader: "somebody noticed" and "somebody said" are
 * different kinds of evidence, and a server-side merge would flatten them
 * before they got here.
 */
export type TimelineEntry =
    | { kind: 'observation'; at: string | null; observation: Observation }
    | { kind: 'feedback'; at: string | null; feedback: Feedback };
