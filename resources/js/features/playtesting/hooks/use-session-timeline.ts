import { useMemo } from 'react';
import type { Feedback, Observation, TimelineEntry } from '../types/playtest';

/**
 * The session's evidence, interleaved into one chronological account.
 *
 * Merged here rather than on the server, and that is the deliberate half of
 * the decision: the two are fetched separately so the distinction between
 * "somebody noticed" and "somebody said" survives as far as this function,
 * which keeps it — every entry stays tagged with which kind it is, so the
 * screen can draw a designer's observation differently from a player's own
 * words.
 *
 * Entries with no moment sort last rather than first. An undated observation
 * was written up after the session, and floating it to the top of the account
 * would put the epilogue before the game.
 */
export function useSessionTimeline(
    observations: Observation[],
    feedback: Feedback[],
): TimelineEntry[] {
    return useMemo(() => {
        const entries: TimelineEntry[] = [
            ...observations.map((observation): TimelineEntry => ({
                kind: 'observation',
                at: observation.occurred_at,
                observation,
            })),
            ...feedback.map((item): TimelineEntry => ({
                kind: 'feedback',
                at: item.created_at,
                feedback: item,
            })),
        ];

        return entries.sort((a, b) => {
            if (a.at === null && b.at === null) {
                return 0;
            }

            if (a.at === null) {
                return 1;
            }

            if (b.at === null) {
                return -1;
            }

            return Date.parse(a.at) - Date.parse(b.at);
        });
    }, [observations, feedback]);
}
