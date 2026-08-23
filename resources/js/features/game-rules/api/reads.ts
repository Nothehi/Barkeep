import rulesApi from '@/routes/api/workspaces/games/versions/rule-sets';
import type {
    ConditionGroup,
    GamePhase,
    GameRule,
    Outcome,
    PhaseTransition,
    RuleAction,
    RuleCondition,
    RuleEffect,
    RuleGraph,
    RuleMechanic,
    RuleReference,
    RuleRequirement,
    RuleSet,
    RuleSetAnalysis,
    RuleTrigger,
} from '../types/game-rules';
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
 * version*, because a rule system belongs to a version rather than to a game — there is no endpoint in this
 * module that reaches a rule set without saying which state of the design it describes.
 */

type Scope = { workspace: string; game: string; version: number };
type RuleSetScope = Scope & { ruleSet: string };

export async function getRuleSets(
    scope: Scope,
    signal?: AbortSignal,
): Promise<RuleSet[]> {
    return unwrap(
        await request<{ data: RuleSet[] }>({
            method: 'get',
            url: rulesApi.index.url(scope),
            signal,
        }),
    );
}

export async function getRuleSet(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleSet> {
    return unwrap(
        await request<{ data: RuleSet }>({
            method: 'get',
            url: rulesApi.show.url(scope),
            signal,
        }),
    );
}

export async function getRules(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<GameRule[]> {
    return unwrap(
        await request<{ data: GameRule[] }>({
            method: 'get',
            url: rulesApi.rules.index.url(scope),
            signal,
        }),
    );
}

export async function getRule(
    scope: RuleSetScope & { gameRule: string },
    signal?: AbortSignal,
): Promise<GameRule> {
    return unwrap(
        await request<{ data: GameRule }>({
            method: 'get',
            url: rulesApi.rules.show.url(scope),
            signal,
        }),
    );
}

export async function getMechanics(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleMechanic[]> {
    return unwrap(
        await request<{ data: RuleMechanic[] }>({
            method: 'get',
            url: rulesApi.mechanics.index.url(scope),
            signal,
        }),
    );
}

export async function getPhases(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<GamePhase[]> {
    return unwrap(
        await request<{ data: GamePhase[] }>({
            method: 'get',
            url: rulesApi.phases.index.url(scope),
            signal,
        }),
    );
}

export async function getTransitions(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<PhaseTransition[]> {
    return unwrap(
        await request<{ data: PhaseTransition[] }>({
            method: 'get',
            url: rulesApi.transitions.index.url(scope),
            signal,
        }),
    );
}

export async function getRuleActions(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleAction[]> {
    return unwrap(
        await request<{ data: RuleAction[] }>({
            method: 'get',
            url: rulesApi.actions.index.url(scope),
            signal,
        }),
    );
}

export async function getRuleAction(
    scope: RuleSetScope & { ruleAction: string },
    signal?: AbortSignal,
): Promise<RuleAction> {
    return unwrap(
        await request<{ data: RuleAction }>({
            method: 'get',
            url: rulesApi.actions.show.url(scope),
            signal,
        }),
    );
}

export async function getRequirements(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleRequirement[]> {
    return unwrap(
        await request<{ data: RuleRequirement[] }>({
            method: 'get',
            url: rulesApi.requirements.index.url(scope),
            signal,
        }),
    );
}

export async function getEffects(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleEffect[]> {
    return unwrap(
        await request<{ data: RuleEffect[] }>({
            method: 'get',
            url: rulesApi.effects.index.url(scope),
            signal,
        }),
    );
}

export async function getConditions(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleCondition[]> {
    return unwrap(
        await request<{ data: RuleCondition[] }>({
            method: 'get',
            url: rulesApi.conditions.index.url(scope),
            signal,
        }),
    );
}

export async function getConditionGroups(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<ConditionGroup[]> {
    return unwrap(
        await request<{ data: ConditionGroup[] }>({
            method: 'get',
            url: rulesApi.conditionGroups.index.url(scope),
            signal,
        }),
    );
}

export async function getTriggers(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleTrigger[]> {
    return unwrap(
        await request<{ data: RuleTrigger[] }>({
            method: 'get',
            url: rulesApi.triggers.index.url(scope),
            signal,
        }),
    );
}

export async function getVictoryConditions(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<Outcome[]> {
    return unwrap(
        await request<{ data: Outcome[] }>({
            method: 'get',
            url: rulesApi.victoryConditions.index.url(scope),
            signal,
        }),
    );
}

export async function getDefeatConditions(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<Outcome[]> {
    return unwrap(
        await request<{ data: Outcome[] }>({
            method: 'get',
            url: rulesApi.defeatConditions.index.url(scope),
            signal,
        }),
    );
}

export async function getGameEndConditions(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<Outcome[]> {
    return unwrap(
        await request<{ data: Outcome[] }>({
            method: 'get',
            url: rulesApi.endConditions.index.url(scope),
            signal,
        }),
    );
}

export async function getRuleReferences(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleReference[]> {
    return unwrap(
        await request<{ data: RuleReference[] }>({
            method: 'get',
            url: rulesApi.references.index.url(scope),
            signal,
        }),
    );
}

/**
 * Everything the dashboard draws, in one read.
 *
 * Silent on the server: unlike the POST beside it, this dispatches nothing. A page refresh is not a
 * decision.
 */
export async function getRuleSetAnalysis(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleSetAnalysis> {
    return unwrap(
        await request<{ data: RuleSetAnalysis }>({
            method: 'get',
            url: rulesApi.analysis.show.url(scope),
            signal,
        }),
    );
}

export async function getRuleGraph(
    scope: RuleSetScope,
    signal?: AbortSignal,
): Promise<RuleGraph> {
    return unwrap(
        await request<{ data: RuleGraph }>({
            method: 'get',
            url: rulesApi.graph.url(scope),
            signal,
        }),
    );
}
