import type { Game, GamePermissions } from '../types/game';

/**
 * Everything denied — what an absent game is worth.
 */
const NOTHING: GamePermissions = {
    canView: false,
    canUpdate: false,
    canChangeStatus: false,
    canChangeDesignPhase: false,
    canArchive: false,
    canCreateVersion: false,
};

/**
 * What the signed in account may do with the given game.
 *
 * The answers come from the server's own policy, which is the only thing that
 * knows them. Recomputing them here from a role and a status would be a
 * second, divergent implementation of the rules — and it would have to know
 * that an archived game is read-only, that a suspended workspace hides
 * everything in it, and that archiving is an admin's job.
 *
 * Use this to decide what the interface offers. It is not a security
 * boundary: hiding a button stops nobody from sending the request, and every
 * action is authorized again server side when they do.
 */
export function useGamePermissions(
    game: Game | null | undefined,
): GamePermissions {
    return game?.permissions ?? NOTHING;
}
