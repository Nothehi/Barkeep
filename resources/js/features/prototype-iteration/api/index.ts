/**
 * The prototype and iteration feature's server calls.
 *
 * Split by direction rather than by resource:
 *
 * - reads go over the JSON API and return data (`get*`);
 * - writes are Inertia visits and return nothing, because the server answers them with a redirect and, where
 *   it is worth saying, a flash message.
 *
 * The writes matter more than usual here. An iteration screen shows the same cycle four ways at once — a
 * header with counts, a list of changes, a summary panel and a timeline — and every write moves all four.
 * Coming back as a reloaded page is what keeps them agreeing with each other and with the server.
 */

export { PrototypeApiError } from './client';
export type { MutationOptions } from './mutation';
export {
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
} from './reads';
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
    rejectDecision,
    startExperiment,
    startIteration,
    updateDecision,
    updateDesignChange,
    updateExperiment,
    updateIteration,
    updatePrototype,
} from './writes';
