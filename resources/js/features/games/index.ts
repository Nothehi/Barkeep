/**
 * The GameDesign module's client surface.
 *
 * Pages under `resources/js/pages/games` are thin wrappers over the page
 * components here; Inertia requires page components to live under `pages/`,
 * so the reusable parts live in the feature instead.
 */

export {
    archiveGame,
    archiveMechanic,
    changeDesignPhase,
    changeGameStatus,
    createGame,
    createGameVersion,
    createMechanic,
    GameApiError,
    getGame,
    getGames,
    getGameVersions,
    updateDesignRecord,
    updateGame,
    updateMechanic,
} from './api';
export type { MechanicInput, MutationOptions } from './api';
export { default as ChangeStatusDialog } from './components/change-status-dialog';
export { default as CreateGameDialog } from './components/create-game-dialog';
export { default as CreateMechanicDialog } from './components/create-mechanic-dialog';
export { default as DesignRecordForm } from './components/design-record-form';
export { default as DesignSummary } from './components/design-summary';
export { default as MechanicList } from './components/mechanic-list';
export { default as MechanicsPicker } from './components/mechanics-picker';
export { default as CreateVersionDialog } from './components/create-version-dialog';
export { default as DesignPhaseBadge } from './components/design-phase-badge';
export { default as DesignPhasePicker } from './components/design-phase-picker';
export { default as EditGameDialog } from './components/edit-game-dialog';
export { default as GameCard } from './components/game-card';
export { default as GameHeader } from './components/game-header';
export { default as GameList } from './components/game-list';
export { default as GameProgress } from './components/game-progress';
export { default as GameStatusBadge } from './components/game-status-badge';
export { default as GameVersionList } from './components/game-version-list';
export { useCreateGame } from './hooks/use-create-game';
export type { UseCreateGameResult } from './hooks/use-create-game';
export { useGame } from './hooks/use-game';
export { useGamePermissions } from './hooks/use-game-permissions';
export { useGames } from './hooks/use-games';
export type { UseGamesResult } from './hooks/use-games';
export { useGameVersions } from './hooks/use-game-versions';
export type { UseGameVersionsResult } from './hooks/use-game-versions';
export { useUpdateDesignRecord } from './hooks/use-update-design-record';
export type { UseUpdateDesignRecordResult } from './hooks/use-update-design-record';
export { useUpdateGame } from './hooks/use-update-game';
export type { UseUpdateGameResult } from './hooks/use-update-game';
export { default as GamePage } from './pages/game-page';
export { default as GamesPage } from './pages/games-page';
export { default as GameSettingsPage } from './pages/game-settings-page';
export { default as GameVersionPage } from './pages/game-version-page';
export { default as GameVersionsPage } from './pages/game-versions-page';
export { default as MechanicsPage } from './pages/mechanics-page';
export * from './schemas/design-record';
export * from './schemas/game';
export type {
    DesignPhase,
    Game,
    GameDashboard,
    GameFilters,
    GameOptions,
    GamePermissions,
    GameStatus,
    GameSummary,
    GameTransition,
    GameVersion,
} from './types/game';
export type {
    Complexity,
    ComplexityOptions,
    DesignRecord,
} from './types/design-record';
export type {
    Mechanic,
    MechanicCategory,
    MechanicOptions,
    MechanicPermissions,
    MechanicStatus,
} from './types/mechanic';
