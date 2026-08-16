import type { Playtest, PlaytestPermissions } from '../types/playtest';

/**
 * Everything denied — what an absent playtest is worth.
 */
const NOTHING: PlaytestPermissions = {
    canView: false,
    canUpdate: false,
    canRecordConclusion: false,
    canComplete: false,
    canCancel: false,
    canCreateSession: false,
};

/**
 * What the signed in account may do with the given playtest.
 *
 * The answers come from the server's own policy, which is the only thing that
 * knows them. Recomputing them here from a status and a role would be a
 * second, divergent implementation of the rules — and it would have to know
 * that an archived game freezes its playtests, that a completed playtest still
 * accepts a conclusion, and that a cancelled one does not.
 *
 * Use this to decide what the interface offers. It is not a security boundary:
 * hiding a button stops nobody from sending the request, and every action is
 * authorized again server side when they do.
 */
export function usePlaytestPermissions(
    playtest: Playtest | null | undefined,
): PlaytestPermissions {
    return playtest?.permissions ?? NOTHING;
}
