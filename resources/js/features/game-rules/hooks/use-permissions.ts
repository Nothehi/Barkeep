import type { RuleSet, RuleSetPermissions } from '../types/game-rules';

/**
 * What the signed in account may do with a rule system, as the server worked it out.
 *
 * A hook rather than a plain property read so that every screen goes through one place, and so that a rule
 * set arriving without its permission map degrades to "may do nothing" rather than crashing on an undefined.
 *
 * `canEdit` is the one nearly every control on these screens reads: "may the rules inside this set be
 * changed?" is a single question with a single answer, and asking it once is what stops sixteen kinds of
 * control from drifting apart as the rules change.
 *
 * `canClone` is the one that matters when `canEdit` is false — which is the normal state of a rule set that
 * is in play. An interface that only knew `canEdit` would show a designer a read-only screen and no way
 * forward; showing "Clone to a new draft" is the whole affordance the module's lifecycle depends on.
 *
 * These decide what the interface offers, never what the server allows. Every one is checked again on the
 * request that performs the action.
 */
export function useRulePermissions(ruleSet: RuleSet): RuleSetPermissions {
    return (
        ruleSet.permissions ?? {
            canView: false,
            canRename: false,
            canEdit: false,
            canActivate: false,
            canArchive: false,
            canClone: false,
        }
    );
}
