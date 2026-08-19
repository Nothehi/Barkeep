import balanceApi from '@/routes/api/workspaces/games/versions/balance-profiles';
import type {
    BalanceAnalysis,
    BalanceAssumption,
    BalanceObservation,
    BalanceProfile,
    BalanceScenario,
    BalanceSnapshot,
    BalanceVariable,
    EconomyAction,
    ResourceFlow,
    ResourceType,
} from '../types/game-economy';
import { request, unwrap } from './client';

/**
 * Every read this feature performs.
 *
 * Gathered in one file rather than split one-per-function, because they are all the same three lines and the
 * split would make the shape harder to see than the code. The writes are the interesting half and they live
 * separately, in `./writes`.
 *
 * Every address is built by Wayfinder from the route table, so a renamed route breaks the type check rather
 * than producing a 404 at runtime. Every one of them carries the workspace, the game *and the design
 * version*, because a balance configuration belongs to a version rather than to a game — there is no
 * endpoint in this module that reaches a profile without saying which state of the design it configures.
 */

type Scope = { workspace: string; game: string; version: number };

export async function getBalanceProfiles(
    { workspace, game, version }: Scope,
    signal?: AbortSignal,
): Promise<BalanceProfile[]> {
    return unwrap(
        await request<{ data: BalanceProfile[] }>({
            method: 'get',
            url: balanceApi.index.url({ workspace, game, version }),
            signal,
        }),
    );
}

export async function getBalanceProfile(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<BalanceProfile> {
    return unwrap(
        await request<{ data: BalanceProfile }>({
            method: 'get',
            url: balanceApi.show.url({ workspace, game, version, profile }),
            signal,
        }),
    );
}

export async function getResources(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<ResourceType[]> {
    return unwrap(
        await request<{ data: ResourceType[] }>({
            method: 'get',
            url: balanceApi.resources.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

export async function getResourceFlows(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<ResourceFlow[]> {
    return unwrap(
        await request<{ data: ResourceFlow[] }>({
            method: 'get',
            url: balanceApi.flows.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

export async function getEconomyActions(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<EconomyAction[]> {
    return unwrap(
        await request<{ data: EconomyAction[] }>({
            method: 'get',
            url: balanceApi.actions.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

export async function getEconomyAction(
    { workspace, game, version }: Scope,
    profile: string,
    economyAction: string,
    signal?: AbortSignal,
): Promise<EconomyAction> {
    return unwrap(
        await request<{ data: EconomyAction }>({
            method: 'get',
            url: balanceApi.actions.show.url({
                workspace,
                game,
                version,
                profile,
                economyAction,
            }),
            signal,
        }),
    );
}

export async function getBalanceVariables(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<BalanceVariable[]> {
    return unwrap(
        await request<{ data: BalanceVariable[] }>({
            method: 'get',
            url: balanceApi.variables.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

export async function getBalanceScenarios(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<BalanceScenario[]> {
    return unwrap(
        await request<{ data: BalanceScenario[] }>({
            method: 'get',
            url: balanceApi.scenarios.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

export async function getBalanceAssumptions(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<BalanceAssumption[]> {
    return unwrap(
        await request<{ data: BalanceAssumption[] }>({
            method: 'get',
            url: balanceApi.assumptions.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

export async function getBalanceObservations(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<BalanceObservation[]> {
    return unwrap(
        await request<{ data: BalanceObservation[] }>({
            method: 'get',
            url: balanceApi.observations.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

/**
 * The findings, read without announcing that anybody looked.
 *
 * The silent half of the pair. Pressing "Analyse" goes through {@link analyseBalanceProfile} instead, which
 * computes exactly the same numbers and records that a person asked for them — whether a team checks the
 * economy before every playtest or only after something goes wrong is a fact about their process, and a
 * dashboard that announced every page load would bury it.
 */
export async function getBalanceAnalysis(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<BalanceAnalysis> {
    return unwrap(
        await request<{ data: BalanceAnalysis }>({
            method: 'get',
            url: balanceApi.analysis.show.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}

export async function getBalanceSnapshots(
    { workspace, game, version }: Scope,
    profile: string,
    signal?: AbortSignal,
): Promise<BalanceSnapshot[]> {
    return unwrap(
        await request<{ data: BalanceSnapshot[] }>({
            method: 'get',
            url: balanceApi.snapshots.index.url({
                workspace,
                game,
                version,
                profile,
            }),
            signal,
        }),
    );
}
