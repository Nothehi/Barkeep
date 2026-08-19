import { router } from '@inertiajs/react';
import iterations from '@/routes/iterations';
import prototypes from '@/routes/prototypes';
import type {
    CompleteExperimentInput,
    CompleteIterationInput,
    CreateArtifactInput,
    CreateIterationInput,
    CreatePrototypeInput,
    CreatePrototypeVersionInput,
    DecisionInput,
    DesignChangeInput,
    EvidenceInput,
    ExperimentInput,
    NextGameVersionInput,
    UpdateIterationInput,
    UpdatePrototypeInput,
} from '../schemas/prototype-iteration';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Every write this feature performs, as Inertia visits.
 *
 * None of these returns anything. The server answers each with a redirect and a flash message, so the
 * reloaded page brings the new record, the recomputed summary and the updated timeline together — see
 * `./mutation` for why that matters on a screen that shows the same cycle four ways at once.
 *
 * Two shapes are worth pointing at. Attaching a playtest sends an id in the body rather than in the URL,
 * because no route in this module binds a model belonging to Playtesting; detaching addresses the link.
 * And cutting the next game version is a deliberate, separate action — nothing here does it as a side
 * effect of completing a cycle.
 */

type Scope = { workspace: string; game: string };

export function createPrototype(
    { workspace, game }: Scope,
    input: CreatePrototypeInput,
    options: MutationOptions = {},
): void {
    router.post(
        prototypes.store.url({ workspace, game }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updatePrototype(
    { workspace, game }: Scope,
    prototype: string,
    input: UpdatePrototypeInput,
    options: MutationOptions = {},
): void {
    router.patch(
        prototypes.update.url({ workspace, game, prototype }),
        { ...input },
        toVisitOptions(options),
    );
}

/**
 * Put a prototype away for good.
 *
 * Its own action rather than a status field, because it cannot be undone — the screen asks for confirmation
 * before calling this.
 */
export function archivePrototype(
    { workspace, game }: Scope,
    prototype: string,
    options: MutationOptions = {},
): void {
    router.post(
        prototypes.archive.url({ workspace, game, prototype }),
        {},
        toVisitOptions(options),
    );
}

/**
 * Cut the next state of a prototype.
 *
 * Both fields are optional, which is load-bearing: the module refuses edits to a version anything has been
 * built on, and that refusal is only reasonable if this costs nothing.
 */
export function createPrototypeVersion(
    { workspace, game }: Scope,
    prototype: string,
    input: CreatePrototypeVersionInput,
    options: MutationOptions = {},
): void {
    router.post(
        prototypes.versions.store.url({ workspace, game, prototype }),
        { ...input },
        toVisitOptions(options),
    );
}

/**
 * Attach a file to a state of a prototype.
 *
 * `forceFormData` because there is a file in here; Inertia would otherwise send JSON and drop it.
 */
export function createArtifact(
    { workspace, game }: Scope,
    prototype: string,
    prototypeVersion: number,
    input: CreateArtifactInput,
    options: MutationOptions = {},
): void {
    router.post(
        prototypes.versions.artifacts.store.url({
            workspace,
            game,
            prototype,
            prototypeVersion,
        }),
        {
            file: input.file,
            name: input.name,
            type: input.type === '' ? null : input.type,
        },
        { ...toVisitOptions(options), forceFormData: true },
    );
}

export function deleteArtifact(
    { workspace, game }: Scope,
    prototype: string,
    prototypeVersion: number,
    artifact: string,
    options: MutationOptions = {},
): void {
    router.delete(
        prototypes.versions.artifacts.destroy.url({
            workspace,
            game,
            prototype,
            prototypeVersion,
            artifact,
        }),
        toVisitOptions(options),
    );
}

/**
 * The address a download is fetched from.
 *
 * A URL rather than a visit, because the browser navigates to it directly. There is no public or signed link
 * to an artifact: this route authorizes on every request and streams the bytes itself.
 */
export function artifactDownloadUrl(
    { workspace, game }: Scope,
    prototype: string,
    prototypeVersion: number,
    artifact: string,
): string {
    return prototypes.versions.artifacts.download.url({
        workspace,
        game,
        prototype,
        prototypeVersion,
        artifact,
    });
}

export function createIteration(
    { workspace, game }: Scope,
    input: CreateIterationInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.store.url({ workspace, game }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateIteration(
    { workspace, game }: Scope,
    iteration: string,
    input: Partial<UpdateIterationInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        iterations.update.url({ workspace, game, iteration }),
        { ...input },
        toVisitOptions(options),
    );
}

export function startIteration(
    { workspace, game }: Scope,
    iteration: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.start.url({ workspace, game, iteration }),
        {},
        toVisitOptions(options),
    );
}

/**
 * Close a cycle.
 *
 * Both fields are required by the server, and the form refuses to submit without them — an iteration with no
 * outcome is a period of time rather than a turn of a loop the next turn can be built on.
 */
export function completeIteration(
    { workspace, game }: Scope,
    iteration: string,
    input: CompleteIterationInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.complete.url({ workspace, game, iteration }),
        { ...input },
        toVisitOptions(options),
    );
}

export function cancelIteration(
    { workspace, game }: Scope,
    iteration: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.cancel.url({ workspace, game, iteration }),
        {},
        toVisitOptions(options),
    );
}

export function createDesignChange(
    { workspace, game }: Scope,
    iteration: string,
    input: DesignChangeInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.changes.store.url({ workspace, game, iteration }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateDesignChange(
    { workspace, game }: Scope,
    iteration: string,
    change: string,
    input: DesignChangeInput,
    options: MutationOptions = {},
): void {
    router.patch(
        iterations.changes.update.url({ workspace, game, iteration, change }),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteDesignChange(
    { workspace, game }: Scope,
    iteration: string,
    change: string,
    options: MutationOptions = {},
): void {
    router.delete(
        iterations.changes.destroy.url({ workspace, game, iteration, change }),
        toVisitOptions(options),
    );
}

export function createExperiment(
    { workspace, game }: Scope,
    iteration: string,
    input: ExperimentInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.experiments.store.url({ workspace, game, iteration }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateExperiment(
    { workspace, game }: Scope,
    iteration: string,
    experiment: string,
    input: ExperimentInput,
    options: MutationOptions = {},
): void {
    router.patch(
        iterations.experiments.update.url({
            workspace,
            game,
            iteration,
            experiment,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function startExperiment(
    { workspace, game }: Scope,
    iteration: string,
    experiment: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.experiments.start.url({
            workspace,
            game,
            iteration,
            experiment,
        }),
        {},
        toVisitOptions(options),
    );
}

/**
 * Record what an experiment actually produced.
 *
 * The only call that may write the after half of an experiment, which is what makes the before half worth
 * reading: the prediction was written through a different form, before this one existed.
 */
export function completeExperiment(
    { workspace, game }: Scope,
    iteration: string,
    experiment: string,
    input: CompleteExperimentInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.experiments.complete.url({
            workspace,
            game,
            iteration,
            experiment,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function cancelExperiment(
    { workspace, game }: Scope,
    iteration: string,
    experiment: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.experiments.cancel.url({
            workspace,
            game,
            iteration,
            experiment,
        }),
        {},
        toVisitOptions(options),
    );
}

export function createDecision(
    { workspace, game }: Scope,
    iteration: string,
    input: DecisionInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.decisions.store.url({ workspace, game, iteration }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateDecision(
    { workspace, game }: Scope,
    iteration: string,
    decision: string,
    input: DecisionInput,
    options: MutationOptions = {},
): void {
    router.patch(
        iterations.decisions.update.url({
            workspace,
            game,
            iteration,
            decision,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

/**
 * Agree a conclusion.
 *
 * Terminal — there is no un-accept, here or on the server. A change of mind is a new decision in a later
 * cycle, which is what the screen tells somebody before they press it.
 */
export function acceptDecision(
    { workspace, game }: Scope,
    iteration: string,
    decision: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.decisions.accept.url({
            workspace,
            game,
            iteration,
            decision,
        }),
        {},
        toVisitOptions(options),
    );
}

export function rejectDecision(
    { workspace, game }: Scope,
    iteration: string,
    decision: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.decisions.reject.url({
            workspace,
            game,
            iteration,
            decision,
        }),
        {},
        toVisitOptions(options),
    );
}

export function deferDecision(
    { workspace, game }: Scope,
    iteration: string,
    decision: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.decisions.defer.url({
            workspace,
            game,
            iteration,
            decision,
        }),
        {},
        toVisitOptions(options),
    );
}

export function createEvidence(
    { workspace, game }: Scope,
    iteration: string,
    decision: string,
    input: EvidenceInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.decisions.evidence.store.url({
            workspace,
            game,
            iteration,
            decision,
        }),
        {
            type: input.type,
            reference_id: input.reference_id === '' ? null : input.reference_id,
            description: input.description,
        },
        toVisitOptions(options),
    );
}

/**
 * Attach a playtest to a cycle.
 *
 * The playtest travels in the body rather than the URL, so no request from this feature names a record
 * belonging to Playtesting in its address.
 */
export function attachPlaytest(
    { workspace, game }: Scope,
    iteration: string,
    playtestId: string,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.playtests.store.url({ workspace, game, iteration }),
        { playtest_id: playtestId },
        toVisitOptions(options),
    );
}

/**
 * Remove a playtest that did not test this cycle after all.
 *
 * Addresses the association rather than the playtest, which is what keeps the whole route inside this module.
 */
export function detachPlaytest(
    { workspace, game }: Scope,
    iteration: string,
    link: string,
    options: MutationOptions = {},
): void {
    router.delete(
        iterations.playtests.destroy.url({ workspace, game, iteration, link }),
        toVisitOptions(options),
    );
}

/**
 * Cut the next design version of the game from what a cycle concluded.
 *
 * The point at which the design loop closes, and it closes because a person pressed something. Completing an
 * iteration never does this.
 */
export function createNextGameVersion(
    { workspace, game }: Scope,
    iteration: string,
    input: NextGameVersionInput,
    options: MutationOptions = {},
): void {
    router.post(
        iterations.gameVersion.store.url({ workspace, game, iteration }),
        { ...input },
        toVisitOptions(options),
    );
}
