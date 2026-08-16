import type { PlaytestSession, SessionPermissions } from '../types/playtest';

/**
 * Everything denied — what an absent session is worth.
 */
const NOTHING: SessionPermissions = {
    canView: false,
    canUpdate: false,
    canStart: false,
    canComplete: false,
    canCancel: false,
    canManageParticipants: false,
    canCreateObservation: false,
    canManageObservations: false,
    canCreateFeedback: false,
    canManageFeedback: false,
};

/**
 * What the signed in account may do with the given session.
 *
 * Longer than the playtest map because a live session offers more, and because
 * the evidence abilities come apart from the lifecycle ones: an ended session
 * still shows everything in it and offers no way to add to it.
 *
 * A hint for the interface, not a grant. Every ability is checked again on the
 * request that performs the action.
 */
export function useSessionPermissions(
    session: PlaytestSession | null | undefined,
): SessionPermissions {
    return session?.permissions ?? NOTHING;
}
