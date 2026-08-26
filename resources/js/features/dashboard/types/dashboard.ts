/**
 * The shapes the app's home screen is sent.
 *
 * Mirrors what `App\Http\Controllers\DashboardController` renders. That is the
 * authoritative shape — when it changes, change this too.
 *
 * The dashboard is the one screen that reads several bounded contexts at once,
 * so these types are assembled from three of them rather than belonging to
 * any: games from GameDesign, playtests from Playtesting, and the prototyping
 * figures from PrototypeIteration.
 */

import type { DesignPhase, GameStatus, GameSummary } from '@/features/games';
import type {
    PlaytestStatus,
    PlaytestSummaryData,
} from '@/features/playtesting';

/**
 * One bar of a distribution: a value, how it is worded, and how many things
 * are sitting in it.
 *
 * The label comes from the server because the enum that owns it is the only
 * thing entitled to word it — a second copy in TypeScript would be a second
 * opinion waiting to go stale.
 */
export type Tally<Value extends string> = {
    value: Value;
    label: string;
    count: number;
};

/**
 * A design phase's share of the workspace, with its place in the arc.
 *
 * The position comes along so the phases can be drawn in order without the
 * client keeping its own copy of that order.
 */
export type PhaseTally = Tally<DesignPhase> & { position: number };

/**
 * A playtest as it appears on a list that spans the whole workspace.
 *
 * The game is what a studio-wide row needs and a game-scoped one does not: a
 * title identifies an investigation inside one project and nothing at all
 * across four. It is also what makes the row clickable, since every playtest
 * address is nested under its game's.
 */
export type WorkspacePlaytest = PlaytestSummaryData & {
    game: { name: string | null; slug: string | null };
};

export type DashboardGames = {
    total: number;
    versions_count: number;
    by_status: Tally<GameStatus>[];
    by_design_phase: PhaseTally[];
    recent: { data: GameSummary[] };
};

export type DashboardPlaytesting = {
    total: number;
    sessions_count: number;
    by_status: Tally<PlaytestStatus>[];
    recent: { data: WorkspacePlaytest[] };
};

/**
 * How much building and iterating the studio has done.
 *
 * Three counts rather than a distribution, because that is what the module can
 * say honestly at this altitude — which changes, whose decisions and what the
 * evidence said belong to an iteration's own screen, where there is room to
 * read them.
 */
export type DashboardIteration = {
    prototypes_count: number;
    iterations_count: number;
    open_iterations_count: number;
};
