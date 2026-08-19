import type {
    BalanceProfile,
    BalanceProfilePermissions,
} from '../types/game-economy';

/**
 * What the signed in account may do with a balance configuration, as the server worked it out.
 *
 * A hook rather than a plain property read so that every screen goes through one place, and so that a
 * profile arriving without its permission map degrades to "may do nothing" rather than crashing on an
 * undefined.
 *
 * `canConfigure` is the one nearly every control on these screens reads: "may the configuration inside this
 * profile be changed?" is a single question with a single answer, and asking it once is what stops a dozen
 * controls from drifting apart as the rules change.
 *
 * These decide what the interface offers, never what the server allows. Every one is checked again on the
 * request that performs the action.
 */
export function useBalancePermissions(
    profile: BalanceProfile,
): BalanceProfilePermissions {
    return (
        profile.permissions ?? {
            canView: false,
            canUpdate: false,
            canActivate: false,
            canArchive: false,
            canConfigure: false,
            canCreateSnapshot: false,
        }
    );
}
