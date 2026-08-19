/**
 * The PrototypeIteration module's client surface.
 *
 * Pages under `resources/js/pages/prototypes` and `resources/js/pages/iterations` are thin wrappers over the
 * page components here; Inertia requires page components to live under `pages/`, so the reusable parts live in
 * the feature instead.
 */

export {
    acceptDecision,
    archivePrototype,
    artifactDownloadUrl,
    attachPlaytest,
    cancelExperiment,
    cancelIteration,
    completeExperiment,
    completeIteration,
    createArtifact,
    createDecision,
    createDesignChange,
    createEvidence,
    createExperiment,
    createIteration,
    createNextGameVersion,
    createPrototype,
    createPrototypeVersion,
    deferDecision,
    deleteArtifact,
    deleteDesignChange,
    detachPlaytest,
    getDecisionEvidence,
    getDecisions,
    getDesignChanges,
    getExperiments,
    getIteration,
    getIterationPlaytests,
    getIterations,
    getIterationSummary,
    getIterationTimeline,
    getPrototype,
    getPrototypeArtifacts,
    getPrototypes,
    getPrototypeVersions,
    PrototypeApiError,
    rejectDecision,
    startExperiment,
    startIteration,
    updateDecision,
    updateDesignChange,
    updateExperiment,
    updateIteration,
    updatePrototype,
} from './api';
export type { MutationOptions } from './api';
export { default as ArtifactList } from './components/artifact-list';
export { default as CompleteIterationDialog } from './components/complete-iteration-dialog';
export { default as CreateIterationDialog } from './components/create-iteration-dialog';
export { default as CreatePrototypeDialog } from './components/create-prototype-dialog';
export { default as DecisionEvidence } from './components/decision-evidence';
export { default as DecisionList } from './components/decision-list';
export { default as DesignChangeList } from './components/design-change-list';
export { default as ExperimentList } from './components/experiment-list';
export { default as IterationCard } from './components/iteration-card';
export { default as IterationHeader } from './components/iteration-header';
export { default as IterationList } from './components/iteration-list';
export { default as IterationSummary } from './components/iteration-summary';
export { default as IterationTimeline } from './components/iteration-timeline';
export { default as PrototypeCard } from './components/prototype-card';
export { default as PrototypeHeader } from './components/prototype-header';
export { default as PrototypeList } from './components/prototype-list';
export { default as PrototypeVersionList } from './components/prototype-version-list';
export { default as RelatedPlaytests } from './components/related-playtests';
export {
    DecisionStatusBadge,
    ExperimentStatusBadge,
    IterationOutcomeBadge,
    IterationStatusBadge,
    PrototypeStatusBadge,
} from './components/status-badges';
export { useDesignForm } from './hooks/use-design-form';
export type { FieldErrors, UseDesignFormResult } from './hooks/use-design-form';
export { useIterationFilters } from './hooks/use-iteration-filters';
export type { UseIterationFiltersResult } from './hooks/use-iteration-filters';
export {
    useIterationPermissions,
    usePrototypePermissions,
} from './hooks/use-permissions';
export { usePrototypeFilters } from './hooks/use-prototype-filters';
export type { UsePrototypeFiltersResult } from './hooks/use-prototype-filters';
export { default as IterationPage } from './pages/iteration-page';
export { default as IterationsPage } from './pages/iterations-page';
export { default as PrototypePage } from './pages/prototype-page';
export { default as PrototypesPage } from './pages/prototypes-page';
export { default as PrototypeVersionPage } from './pages/prototype-version-page';
export * from './schemas/prototype-iteration';
export type {
    ArtifactType,
    ChangeCategory,
    CitedEvidence,
    DecisionStatus,
    DescribedOption,
    DesignChange,
    DesignDecision,
    DesignExperiment,
    EvidenceType,
    ExperimentStatus,
    Iteration,
    IterationCard as IterationCardData,
    IterationFilters,
    IterationOptions,
    IterationOutcome,
    IterationPermissions,
    IterationStatus,
    IterationSummary as IterationSummaryData,
    IterationTimeline as IterationTimelineData,
    PlaytestReference,
    Prototype,
    PrototypeArtifact,
    PrototypeCard as PrototypeCardData,
    PrototypeFilters,
    PrototypeOptions,
    PrototypePermissions,
    PrototypeStatus,
    PrototypeType,
    PrototypeVersion,
    SelectablePlaytest,
    TimelineEntry,
    TimelineEntryKind,
    Transition,
    VocabularyOption,
} from './types/prototype-iteration';
