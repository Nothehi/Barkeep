import { Link } from '@inertiajs/react';
import {
    CalendarDays,
    Clock,
    Eye,
    MapPin,
    MessageSquare,
    Users,
} from 'lucide-react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import sessions from '@/routes/playtests/sessions';
import type { PlaytestSession } from '../types/playtest';
import SessionStatusBadge from './session-status-badge';

type SessionCardProps = {
    session: PlaytestSession;
    workspace: string;
    game: string;
    playtest: string;
    index: number;
};

/**
 * One sitting in a playtest's list.
 *
 * Numbered rather than titled, because sessions do not have names — "the third
 * group" is how designers actually refer to them, and the order is the thing
 * that carries meaning.
 *
 * A running session is drawn with a ring so it is findable at a glance by
 * somebody who has the screen open at a table.
 */
export default function SessionCard({
    session,
    workspace,
    game,
    playtest,
    index,
}: SessionCardProps) {
    const when = session.started_at ?? session.planned_at;

    const whenLabel = when
        ? new Date(when).toLocaleString(undefined, {
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
          })
        : null;

    return (
        <Card
            className={
                session.status === 'in_progress'
                    ? 'ring-2 ring-primary/40'
                    : undefined
            }
        >
            <CardHeader className="gap-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Link
                        href={sessions.show.url({
                            workspace,
                            game,
                            playtest,
                            session: session.id,
                        })}
                        className="font-medium hover:underline"
                        data-test={`session-link-${session.id}`}
                    >
                        Session {index + 1}
                    </Link>

                    <SessionStatusBadge
                        status={session.status}
                        label={session.status_label}
                    />
                </div>
            </CardHeader>

            <CardContent className="space-y-3">
                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    {whenLabel && (
                        <span className="inline-flex items-center gap-1">
                            <CalendarDays className="size-3" />
                            {whenLabel}
                        </span>
                    )}

                    {session.location && (
                        <span className="inline-flex items-center gap-1">
                            <MapPin className="size-3" />
                            {session.location}
                        </span>
                    )}

                    {session.duration_label && (
                        <span className="inline-flex items-center gap-1">
                            <Clock className="size-3" />
                            {session.duration_label}
                        </span>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span className="inline-flex items-center gap-1">
                        <Users className="size-3" />
                        {session.participants_count ?? 0}
                    </span>

                    <span className="inline-flex items-center gap-1">
                        <Eye className="size-3" />
                        {session.observations_count ?? 0}
                    </span>

                    <span className="inline-flex items-center gap-1">
                        <MessageSquare className="size-3" />
                        {session.feedback_count ?? 0}
                    </span>
                </div>

                {session.outcome && (
                    <p className="line-clamp-2 text-sm text-muted-foreground">
                        {session.outcome}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
