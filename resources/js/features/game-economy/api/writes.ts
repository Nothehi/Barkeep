import { router } from '@inertiajs/react';
import balance from '@/routes/balance';
import type {
    ActionInput,
    ActionLineInput,
    AssumptionInput,
    CreateProfileInput,
    EffectInput,
    FlowInput,
    ObservationInput,
    ResourceInput,
    ScenarioInput,
    ScenarioVariableInput,
    SnapshotInput,
    VariableInput,
} from '../schemas/game-economy';
import type { BalanceScenarioStatus } from '../types/game-economy';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Every write this feature performs, as Inertia visits.
 *
 * None of these returns anything. The server answers each with a redirect and a flash message, so the
 * reloaded page brings the new record, the recomputed summary and the refreshed findings together — see
 * `./mutation` for why that matters on a screen that shows the same economy five ways at once.
 *
 * ## Empty strings, not zeros
 *
 * The optional amount fields go out as whatever the form holds, including `''`. That is deliberate: an empty
 * string reaches the server as an absent bound, which means *unbounded* — and sending `0` instead would
 * invent a limit the designer never set. Nothing here coerces a blank field into a number.
 */

type Scope = { workspace: string; game: string; version: number };

type ProfileScope = Scope & { profile: string };

type ActionScope = ProfileScope & { economyAction: string };

export function createBalanceProfile(
    { workspace, game, version }: Scope,
    input: CreateProfileInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.store.url({ workspace, game, version }),
        { ...input },
        toVisitOptions({ preserveScroll: false, ...options }),
    );
}

export function updateBalanceProfile(
    { workspace, game, version, profile }: ProfileScope,
    input: CreateProfileInput,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.update.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

export function activateBalanceProfile(
    { workspace, game, version, profile }: ProfileScope,
    options: MutationOptions = {},
): void {
    router.post(
        balance.activate.url({ workspace, game, version, profile }),
        {},
        toVisitOptions(options),
    );
}

export function archiveBalanceProfile(
    { workspace, game, version, profile }: ProfileScope,
    options: MutationOptions = {},
): void {
    router.post(
        balance.archive.url({ workspace, game, version, profile }),
        {},
        toVisitOptions({ preserveScroll: false, ...options }),
    );
}

export function createResource(
    { workspace, game, version, profile }: ProfileScope,
    input: ResourceInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.resources.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateResource(
    { workspace, game, version, profile }: ProfileScope,
    resourceType: string,
    input: Partial<ResourceInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.resources.update.url({
            workspace,
            game,
            version,
            profile,
            resourceType,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteResource(
    { workspace, game, version, profile }: ProfileScope,
    resourceType: string,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.resources.destroy.url({
            workspace,
            game,
            version,
            profile,
            resourceType,
        }),
        toVisitOptions(options),
    );
}

export function createFlow(
    { workspace, game, version, profile }: ProfileScope,
    input: FlowInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.flows.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateFlow(
    { workspace, game, version, profile }: ProfileScope,
    flow: string,
    input: Partial<FlowInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.flows.update.url({ workspace, game, version, profile, flow }),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteFlow(
    { workspace, game, version, profile }: ProfileScope,
    flow: string,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.flows.destroy.url({ workspace, game, version, profile, flow }),
        toVisitOptions(options),
    );
}

export function createEconomyAction(
    { workspace, game, version, profile }: ProfileScope,
    input: ActionInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.actions.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions({ preserveScroll: false, ...options }),
    );
}

export function updateEconomyAction(
    { workspace, game, version, profile, economyAction }: ActionScope,
    input: Partial<ActionInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.actions.update.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteEconomyAction(
    { workspace, game, version, profile, economyAction }: ActionScope,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.actions.destroy.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
        }),
        toVisitOptions({ preserveScroll: false, ...options }),
    );
}

export function addActionCost(
    { workspace, game, version, profile, economyAction }: ActionScope,
    input: ActionLineInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.actions.costs.store.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateActionCost(
    { workspace, game, version, profile, economyAction }: ActionScope,
    cost: string,
    input: Partial<ActionLineInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.actions.costs.update.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
            cost,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function removeActionCost(
    { workspace, game, version, profile, economyAction }: ActionScope,
    cost: string,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.actions.costs.destroy.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
            cost,
        }),
        toVisitOptions(options),
    );
}

export function addActionReward(
    { workspace, game, version, profile, economyAction }: ActionScope,
    input: ActionLineInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.actions.rewards.store.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateActionReward(
    { workspace, game, version, profile, economyAction }: ActionScope,
    reward: string,
    input: Partial<ActionLineInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.actions.rewards.update.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
            reward,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function removeActionReward(
    { workspace, game, version, profile, economyAction }: ActionScope,
    reward: string,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.actions.rewards.destroy.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
            reward,
        }),
        toVisitOptions(options),
    );
}

export function addActionEffect(
    { workspace, game, version, profile, economyAction }: ActionScope,
    input: EffectInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.actions.effects.store.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateActionEffect(
    { workspace, game, version, profile, economyAction }: ActionScope,
    effect: string,
    input: Partial<EffectInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.actions.effects.update.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
            effect,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function removeActionEffect(
    { workspace, game, version, profile, economyAction }: ActionScope,
    effect: string,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.actions.effects.destroy.url({
            workspace,
            game,
            version,
            profile,
            economyAction,
            effect,
        }),
        toVisitOptions(options),
    );
}

export function createBalanceVariable(
    { workspace, game, version, profile }: ProfileScope,
    input: VariableInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.variables.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

/**
 * Change a tunable number.
 *
 * The call the variable table's inline editing makes, which is why it takes a partial: a cell that sends
 * only `value` must not clear the unit, the range or the category around it.
 */
export function updateBalanceVariable(
    { workspace, game, version, profile }: ProfileScope,
    variable: string,
    input: Partial<VariableInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.variables.update.url({
            workspace,
            game,
            version,
            profile,
            variable,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function deleteBalanceVariable(
    { workspace, game, version, profile }: ProfileScope,
    variable: string,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.variables.destroy.url({
            workspace,
            game,
            version,
            profile,
            variable,
        }),
        toVisitOptions(options),
    );
}

export function createBalanceScenario(
    { workspace, game, version, profile }: ProfileScope,
    input: ScenarioInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.scenarios.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateBalanceScenario(
    { workspace, game, version, profile }: ProfileScope,
    scenario: string,
    input: Partial<ScenarioInput> & { status?: BalanceScenarioStatus },
    options: MutationOptions = {},
): void {
    router.patch(
        balance.scenarios.update.url({
            workspace,
            game,
            version,
            profile,
            scenario,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function archiveBalanceScenario(
    { workspace, game, version, profile }: ProfileScope,
    scenario: string,
    options: MutationOptions = {},
): void {
    router.post(
        balance.scenarios.archive.url({
            workspace,
            game,
            version,
            profile,
            scenario,
        }),
        {},
        toVisitOptions(options),
    );
}

/**
 * State a value differently under a hypothetical.
 *
 * Nothing about this touches the base variable. The override is written to a different table, so the
 * guarantee holds wherever the call is made from.
 */
export function setScenarioVariable(
    { workspace, game, version, profile }: ProfileScope,
    scenario: string,
    input: ScenarioVariableInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.scenarios.variables.store.url({
            workspace,
            game,
            version,
            profile,
            scenario,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function removeScenarioVariable(
    { workspace, game, version, profile }: ProfileScope,
    scenario: string,
    override: string,
    options: MutationOptions = {},
): void {
    router.delete(
        balance.scenarios.variables.destroy.url({
            workspace,
            game,
            version,
            profile,
            scenario,
            override,
        }),
        toVisitOptions(options),
    );
}

export function createBalanceAssumption(
    { workspace, game, version, profile }: ProfileScope,
    input: AssumptionInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.assumptions.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateBalanceAssumption(
    { workspace, game, version, profile }: ProfileScope,
    assumption: string,
    input: Partial<AssumptionInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.assumptions.update.url({
            workspace,
            game,
            version,
            profile,
            assumption,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function createBalanceObservation(
    { workspace, game, version, profile }: ProfileScope,
    input: ObservationInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.observations.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

export function updateBalanceObservation(
    { workspace, game, version, profile }: ProfileScope,
    balanceObservation: string,
    input: Partial<ObservationInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        balance.observations.update.url({
            workspace,
            game,
            version,
            profile,
            balanceObservation,
        }),
        { ...input },
        toVisitOptions(options),
    );
}

export function createBalanceSnapshot(
    { workspace, game, version, profile }: ProfileScope,
    input: SnapshotInput,
    options: MutationOptions = {},
): void {
    router.post(
        balance.snapshots.store.url({ workspace, game, version, profile }),
        { ...input },
        toVisitOptions(options),
    );
}

/**
 * Analyse the configuration, and record that somebody did.
 *
 * The dashboard already shows the findings — it reads them silently — so this exists for one reason:
 * pressing "Analyse" is a fact about how a studio works, and this is what publishes it.
 */
export function analyseBalanceProfile(
    { workspace, game, version, profile }: ProfileScope,
    options: MutationOptions = {},
): void {
    router.post(
        balance.analysis.store.url({ workspace, game, version, profile }),
        {},
        toVisitOptions(options),
    );
}

/**
 * The address of the comparison between two frozen configurations.
 *
 * A URL rather than a visit, because the comparison is a page somebody navigates to and can share — and
 * because the pair travels in the query string, where a bookmark keeps it.
 */
export function comparisonUrl(
    { workspace, game, version, profile }: ProfileScope,
    from: string,
    to: string,
): string {
    return balance.snapshots.compare.url(
        { workspace, game, version, profile },
        { query: { from, to } },
    );
}
