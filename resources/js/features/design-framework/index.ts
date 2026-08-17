/**
 * The DesignFramework module's client surface.
 *
 * Pages under `resources/js/pages/frameworks` and `resources/js/pages/games`
 * are thin wrappers over the page components here; Inertia requires page
 * components to live under `pages/`, so the reusable parts live in the feature
 * instead.
 */

export {
    adoptFramework,
    completeChecklistItem,
    completePractice,
    createChecklistItem,
    createContent,
    createFramework,
    createVersion,
    evaluateCriterion,
    moveAdoption,
    moveFramework,
    moveVersion,
    reorderChecklistItem,
    reorderContent,
    respondToPrompt,
    updateChecklistItem,
    updateContent,
    updateFramework,
    updateVersion,
} from './api';
export type {
    ChecklistItemInput,
    ContentInput,
    ContentType,
    FrameworkInput,
    MutationOptions,
    VersionInput,
} from './api';
export { default as AdoptFrameworkPanel } from './components/adopt-framework-panel';
export { default as AnsweredFromDesign } from './components/answered-from-design';
export { default as AdoptionStatusBadge } from './components/adoption-status-badge';
export { default as BuilderSection } from './components/builder-section';
export type { BuilderRow } from './components/builder-section';
export { default as ChecklistPanel } from './components/checklist-panel';
export { default as CreateFrameworkDialog } from './components/create-framework-dialog';
export { default as CreateVersionDialog } from './components/create-version-dialog';
export { default as CriterionList } from './components/criterion-list';
export { default as FrameworkCard } from './components/framework-card';
export { default as FrameworkList } from './components/framework-list';
export { default as FrameworkStatusBadge } from './components/framework-status-badge';
export { default as PhaseNav } from './components/phase-nav';
export { default as PracticeList } from './components/practice-list';
export { default as PrincipleList } from './components/principle-list';
export { default as ProgressBar } from './components/progress-bar';
export { default as PromptList } from './components/prompt-list';
export { default as TransitionButtons } from './components/transition-buttons';
export { default as VersionList } from './components/version-list';
export { useFrameworks } from './hooks/use-frameworks';
export type { UseFrameworksResult } from './hooks/use-frameworks';
export { default as BuilderPage } from './pages/builder-page';
export { default as FrameworkPage } from './pages/framework-page';
export { default as FrameworksPage } from './pages/frameworks-page';
export { default as GameFrameworkPage } from './pages/game-framework-page';
export { default as GameFrameworkPhasePage } from './pages/game-framework-phase-page';
export type {
    Checklist,
    ChecklistItem,
    ChecklistItemProgress,
    ChecklistItemState,
    ChecklistProgress,
    CriterionEvaluation,
    CriterionRating,
    DesignCriterion,
    DesignPhase,
    DesignPractice,
    DesignPrinciple,
    DesignPrompt,
    Framework,
    FrameworkContentStatus,
    FrameworkFilters,
    FrameworkOptions,
    FrameworkPermissions,
    FrameworkProgress,
    FrameworkStatus,
    FrameworkTransition,
    FrameworkVersion,
    FrameworkVersionPermissions,
    GameDesignFacts,
    GameFramework,
    GameFrameworkPermissions,
    GameFrameworkStatus,
    PhaseProgress,
    PracticeCompletion,
    ProgressRatio,
    PromptResponse,
    RatingOption,
} from './types/framework';
