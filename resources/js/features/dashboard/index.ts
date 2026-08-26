/**
 * The app's home screen.
 *
 * The one feature here that is not a bounded context's client surface. The
 * dashboard reads GameDesign, Playtesting and PrototypeIteration together, so
 * it belongs to none of them — the same reason its controller sits in the
 * application rather than in a module.
 *
 * `resources/js/pages/dashboard.tsx` is a thin wrapper over the page component
 * here; Inertia requires page components to live under `pages/`, so the
 * reusable parts live in the feature instead.
 */

export { default as PhaseDistribution } from './components/phase-distribution';
export { default as RecentGames } from './components/recent-games';
export { default as RecentPlaytests } from './components/recent-playtests';
export { default as StatTile } from './components/stat-tile';
export { default as DashboardPage } from './pages/dashboard-page';
export type { DashboardPageProps } from './pages/dashboard-page';
export type {
    DashboardGames,
    DashboardIteration,
    DashboardPlaytesting,
    PhaseTally,
    Tally,
    WorkspacePlaytest,
} from './types/dashboard';
