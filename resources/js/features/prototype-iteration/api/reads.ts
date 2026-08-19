import iterationsApi from '@/routes/api/workspaces/games/iterations';
import prototypesApi from '@/routes/api/workspaces/games/prototypes';
import type {
    CitedEvidence,
    DesignChange,
    DesignDecision,
    DesignExperiment,
    Iteration,
    IterationCard,
    IterationSummary,
    IterationTimeline,
    PlaytestReference,
    Prototype,
    PrototypeArtifact,
    PrototypeCard,
    PrototypeVersion,
} from '../types/prototype-iteration';
import { request, unwrap } from './client';

/**
 * Every read this feature performs.
 *
 * Gathered in one file rather than split one-per-function, because they are all the same three lines and the
 * split would make the shape harder to see than the code. The writes are the interesting half and they live
 * separately, in `./writes`.
 *
 * Every address is built by Wayfinder from the route table, so a renamed route breaks the type check rather
 * than producing a 404 at runtime. Every one of them carries the workspace and the game, because there is no
 * endpoint in this module that reaches a record without its owners — see routes/prototypes.php.
 */

type Query = Record<string, string>;

/**
 * Drop the filters nobody set, so an unfiltered list is a clean URL rather than one full of empty parameters.
 */
function query(filters: Record<string, string | null | undefined>): Query {
    return Object.fromEntries(
        Object.entries(filters).filter((entry): entry is [string, string] =>
            Boolean(entry[1]),
        ),
    );
}

export async function getPrototypes(
    workspace: string,
    game: string,
    filters: { search?: string; status?: string; type?: string } = {},
    signal?: AbortSignal,
): Promise<PrototypeCard[]> {
    return unwrap(
        await request<{ data: PrototypeCard[] }>({
            method: 'get',
            url: prototypesApi.index.url(
                { workspace, game },
                { query: query(filters) },
            ),
            signal,
        }),
    );
}

export async function getPrototype(
    workspace: string,
    game: string,
    prototype: string,
    signal?: AbortSignal,
): Promise<Prototype> {
    return unwrap(
        await request<{ data: Prototype }>({
            method: 'get',
            url: prototypesApi.show.url({ workspace, game, prototype }),
            signal,
        }),
    );
}

export async function getPrototypeVersions(
    workspace: string,
    game: string,
    prototype: string,
    signal?: AbortSignal,
): Promise<PrototypeVersion[]> {
    return unwrap(
        await request<{ data: PrototypeVersion[] }>({
            method: 'get',
            url: prototypesApi.versions.index.url({
                workspace,
                game,
                prototype,
            }),
            signal,
        }),
    );
}

export async function getPrototypeArtifacts(
    workspace: string,
    game: string,
    prototype: string,
    prototypeVersion: number,
    signal?: AbortSignal,
): Promise<PrototypeArtifact[]> {
    return unwrap(
        await request<{ data: PrototypeArtifact[] }>({
            method: 'get',
            url: prototypesApi.versions.artifacts.index.url({
                workspace,
                game,
                prototype,
                prototypeVersion,
            }),
            signal,
        }),
    );
}

export async function getIterations(
    workspace: string,
    game: string,
    filters: {
        search?: string;
        status?: string;
        outcome?: string;
        prototype?: string;
    } = {},
    signal?: AbortSignal,
): Promise<IterationCard[]> {
    return unwrap(
        await request<{ data: IterationCard[] }>({
            method: 'get',
            url: iterationsApi.index.url(
                { workspace, game },
                { query: query(filters) },
            ),
            signal,
        }),
    );
}

export async function getIteration(
    workspace: string,
    game: string,
    iteration: string,
    signal?: AbortSignal,
): Promise<Iteration> {
    return unwrap(
        await request<{ data: Iteration }>({
            method: 'get',
            url: iterationsApi.show.url({ workspace, game, iteration }),
            signal,
        }),
    );
}

/**
 * What a cycle produced, counted on read.
 *
 * Its own endpoint rather than fields on the iteration, because the counts cost several aggregate queries plus
 * a pass through Playtesting — and the header is drawn on every screen in this part of the application while
 * the summary panel is drawn on one.
 */
export async function getIterationSummary(
    workspace: string,
    game: string,
    iteration: string,
    signal?: AbortSignal,
): Promise<IterationSummary> {
    return unwrap(
        await request<{ data: IterationSummary }>({
            method: 'get',
            url: iterationsApi.summary.url({ workspace, game, iteration }),
            signal,
        }),
    );
}

export async function getIterationTimeline(
    workspace: string,
    game: string,
    iteration: string,
    signal?: AbortSignal,
): Promise<IterationTimeline> {
    return unwrap(
        await request<{ data: IterationTimeline }>({
            method: 'get',
            url: iterationsApi.timeline.url({ workspace, game, iteration }),
            signal,
        }),
    );
}

export async function getDesignChanges(
    workspace: string,
    game: string,
    iteration: string,
    signal?: AbortSignal,
): Promise<DesignChange[]> {
    return unwrap(
        await request<{ data: DesignChange[] }>({
            method: 'get',
            url: iterationsApi.changes.index.url({
                workspace,
                game,
                iteration,
            }),
            signal,
        }),
    );
}

export async function getExperiments(
    workspace: string,
    game: string,
    iteration: string,
    signal?: AbortSignal,
): Promise<DesignExperiment[]> {
    return unwrap(
        await request<{ data: DesignExperiment[] }>({
            method: 'get',
            url: iterationsApi.experiments.index.url({
                workspace,
                game,
                iteration,
            }),
            signal,
        }),
    );
}

export async function getDecisions(
    workspace: string,
    game: string,
    iteration: string,
    signal?: AbortSignal,
): Promise<DesignDecision[]> {
    return unwrap(
        await request<{ data: DesignDecision[] }>({
            method: 'get',
            url: iterationsApi.decisions.index.url({
                workspace,
                game,
                iteration,
            }),
            signal,
        }),
    );
}

/**
 * A decision's citations, resolved into the words they point at.
 *
 * Read rather than held, because the excerpt comes live from the context that owns it — a correction to an
 * observation shows up here without anything in this module being rewritten.
 */
export async function getDecisionEvidence(
    workspace: string,
    game: string,
    iteration: string,
    decision: string,
    signal?: AbortSignal,
): Promise<CitedEvidence[]> {
    return unwrap(
        await request<{ data: CitedEvidence[] }>({
            method: 'get',
            url: iterationsApi.decisions.evidence.index.url({
                workspace,
                game,
                iteration,
                decision,
            }),
            signal,
        }),
    );
}

export async function getIterationPlaytests(
    workspace: string,
    game: string,
    iteration: string,
    signal?: AbortSignal,
): Promise<PlaytestReference[]> {
    return unwrap(
        await request<{ data: PlaytestReference[] }>({
            method: 'get',
            url: iterationsApi.playtests.index.url({
                workspace,
                game,
                iteration,
            }),
            signal,
        }),
    );
}
