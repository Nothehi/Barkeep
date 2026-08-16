/**
 * The game shapes the server sends.
 *
 * Mirrors the resources under
 * `Modules\GameDesign\Presentation\Http\Resources`. Those are the
 * authoritative shape — when one changes, change it here too.
 */

import type { User } from '@/features/auth';

/**
 * Where a game project is in its own life.
 *
 * Independent of {@link DesignPhase}: a game can be on hold in the middle of
 * playtesting, and neither value says anything about the other.
 */
export type GameStatus =
    'draft' | 'active' | 'on_hold' | 'completed' | 'archived';

/**
 * How far a game has got in the design process.
 */
export type DesignPhase =
    | 'idea'
    | 'concept'
    | 'core_design'
    | 'prototyping'
    | 'playtesting'
    | 'development'
    | 'production'
    | 'published';

/**
 * What the signed in account may do with a game.
 *
 * Computed from the policy server side, so this is the same answer the
 * request would get. It decides what the interface offers, never what the
 * server allows — every one of these is checked again on the request that
 * performs the action.
 */
export type GamePermissions = {
    canView: boolean;
    canUpdate: boolean;
    canChangeStatus: boolean;
    canChangeDesignPhase: boolean;
    canArchive: boolean;
    canCreateVersion: boolean;
};

/**
 * One lifecycle move a game can currently make, already worded.
 *
 * The transition matrix lives in the domain and this list is derived from it
 * per game, so the interface renders the moves it is given rather than
 * keeping a second copy of the rules that would drift from the first.
 */
export type GameTransition = {
    status: GameStatus;
    label: string;
};

export type Game = {
    id: string;
    workspace_id: string;
    name: string;
    slug: string;
    description: string | null;
    status: GameStatus;
    status_label: string;
    design_phase: DesignPhase;
    design_phase_label: string;
    design_phase_position: number;
    design_phase_count: number;
    created_by: string;
    versions_count?: number;
    created_at: string | null;
    updated_at: string | null;
    permissions: GamePermissions;
    available_transitions: GameTransition[];
};

/**
 * A game as it appears in a list.
 *
 * Smaller than {@link Game} on purpose: cards do not offer lifecycle actions,
 * so the server does not compute permissions or transitions for them.
 */
export type GameSummary = {
    id: string;
    workspace_id: string;
    name: string;
    slug: string;
    description: string | null;
    status: GameStatus;
    status_label: string;
    design_phase: DesignPhase;
    design_phase_label: string;
    design_phase_position: number;
    versions_count?: number;
    updated_at: string | null;
};

export type GameVersion = {
    id: string;
    game_id: string;
    version_number: number;
    label: string;
    name: string | null;
    description: string | null;
    created_by: string;
    creator?: User;
    created_at: string | null;
    updated_at: string | null;
};

/**
 * What a game's overview screen is made of.
 *
 * Deliberately thin. Playtest counts, feedback summaries and progress metrics
 * belong to contexts that do not exist yet.
 */
export type GameDashboard = {
    versions_count: number;
    latest_version: { data: GameVersion } | null;
};

/**
 * The filters currently applied to a games list, echoed back by the server.
 */
export type GameFilters = {
    search: string | null;
    status: GameStatus | null;
    design_phase: DesignPhase | null;
};

/**
 * The values the game screens let somebody choose between.
 *
 * Sent from the server so the labels, the ordering and the set itself have
 * one definition rather than a second one here that goes stale.
 */
export type GameOptions = {
    statuses: { value: GameStatus; label: string }[];
    design_phases: {
        value: DesignPhase;
        label: string;
        description: string;
        position: number;
    }[];
};
