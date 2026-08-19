import { useMemo } from 'react';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import type { BalanceProfile } from '../types/game-economy';

export type BalanceScope = {
    workspace: string;
    game: string;
    version: number;
};

export type ProfileScope = BalanceScope & { profile: string };

/**
 * The address every call in this feature is made against.
 *
 * A balance configuration belongs to a design version rather than to a game, so every URL in the module
 * carries all three segments. Assembling them once per screen — rather than at each of the twenty call
 * sites on the dashboard — is what stops one control from being pointed at the wrong version after somebody
 * adds a prop.
 */
export function useBalanceScope(
    workspace: Workspace,
    game: Game,
    version: GameVersion,
): BalanceScope {
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
 * The same address, narrowed to one configuration.
 */
export function useProfileScope(
    workspace: Workspace,
    game: Game,
    version: GameVersion,
    profile: BalanceProfile,
): ProfileScope {
    const scope = useBalanceScope(workspace, game, version);

    return useMemo(
        () => ({ ...scope, profile: profile.id }),
        [scope, profile.id],
    );
}
