import { useMemo } from 'react';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import type { RuleSet } from '../types/game-rules';

export type RuleScope = {
    workspace: string;
    game: string;
    version: number;
};

export type RuleSetScope = RuleScope & { ruleSet: string };

/**
 * The address every call in this feature is made against.
 *
 * A rule system belongs to a design version rather than to a game, so every URL in the module carries all
 * three segments. Assembling them once per screen — rather than at each of the forty call sites on the
 * dashboard — is what stops one control from being pointed at the wrong version after somebody adds a prop.
 */
export function useRuleScope(
    workspace: Workspace,
    game: Game,
    version: GameVersion,
): RuleScope {
    return useMemo(
        () => ({
            workspace: workspace.slug,
            game: game.slug,
            version: version.version_number,
        }),
        [workspace.slug, game.slug, version.version_number],
    );
}

/**
 * The same address, narrowed to one rule system.
 */
export function useRuleSetScope(
    workspace: Workspace,
    game: Game,
    version: GameVersion,
    ruleSet: RuleSet,
): RuleSetScope {
    const scope = useRuleScope(workspace, game, version);

    return useMemo(
        () => ({ ...scope, ruleSet: ruleSet.id }),
        [scope, ruleSet.id],
    );
}
