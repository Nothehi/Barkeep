import { Eye, MessageSquare, Star } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useSessionTimeline } from '../hooks/use-session-timeline';
import type { Feedback, Observation } from '../types/playtest';

type SessionTimelineProps = {
    observations: Observation[];
    feedback: Feedback[];
};

/**
 * The session as it happened, in order.
 *
 * The two kinds of evidence are interleaved and stay visibly different — an
 * observation is what somebody watching saw, a piece of feedback is what a
 * player said, and a timeline that flattened them into one voice would lose
 * the distinction that makes either worth reading.
 *
 * Entries with no moment sit at the end rather than the start. An observation
 * typed up after the session belongs in the account, but putting it first
 * would place the epilogue before the game.
 *
 * This is the foundation later analysis is meant to build on, which is why it
 * shows what was recorded rather than interpreting any of it.
 */
export default function SessionTimeline({
    observations,
    feedback,
}: SessionTimelineProps) {
    const entries = useSessionTimeline(observations, feedback);

    if (entries.length === 0) {
        return (
            <p
                className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                data-test="timeline-empty"
            >
                Nothing recorded yet. Observations and feedback appear here in
                the order they happened.
            </p>
        );
    }

    return (
        <ol className="space-y-3" data-test="session-timeline">
            {entries.map((entry) => {
                const at = entry.at
                    ? new Date(entry.at).toLocaleTimeString(undefined, {
                          hour: '2-digit',
                          minute: '2-digit',
                      })
                    : 'Later';

                const key =
                    entry.kind === 'observation'
                        ? `observation-${entry.observation.id}`
                        : `feedback-${entry.feedback.id}`;

                return (
                    <li key={key} className="flex gap-3">
                        <div className="w-14 shrink-0 pt-0.5 text-right text-xs text-muted-foreground tabular-nums">
                            {at}
                        </div>

                        <div className="min-w-0 flex-1 space-y-1 border-l pb-1 pl-3">
                            {entry.kind === 'observation' ? (
                                <>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
                                            <Eye className="size-3" />
                                            Observation
                                        </span>

                                        <Badge variant="outline">
                                            {entry.observation.category_label}
                                        </Badge>

                                        {entry.observation.participant && (
                                            <span className="text-xs text-muted-foreground">
                                                about{' '}
                                                {
                                                    entry.observation
                                                        .participant
                                                        .display_name
                                                }
                                            </span>
                                        )}
                                    </div>

                                    <p className="text-sm">
                                        {entry.observation.content}
                                    </p>
                                </>
                            ) : (
                                <>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
                                            <MessageSquare className="size-3" />
                                            Feedback
                                        </span>

                                        <span className="text-xs text-muted-foreground">
                                            {entry.feedback.participant
                                                ?.display_name ?? 'Anonymous'}
                                        </span>

                                        {entry.feedback.rating_label && (
                                            <Badge variant="outline">
                                                <Star className="size-3" />
                                                {entry.feedback.rating_label}
                                            </Badge>
                                        )}
                                    </div>

                                    <p className="text-sm">
                                        “{entry.feedback.content}”
                                    </p>
                                </>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
