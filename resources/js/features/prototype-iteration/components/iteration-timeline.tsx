import { Link } from '@inertiajs/react';
import {
    CheckCircle2,
    CircleSlash,
    FlaskConical,
    PenLine,
    PlayCircle,
    Scale,
    TestTubes,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Badge } from '@/components/ui/badge';
import { useFormatters, useTranslation } from '@/lib/i18n';
import playtests from '@/routes/playtests';
import type {
    IterationTimeline as TimelineData,
    TimelineEntryKind,
} from '../types/prototype-iteration';

type IterationTimelineProps = {
    timeline: TimelineData;
    workspace: string;
    game: string;
};

/**
 * The icon each kind of entry is drawn with.
 *
 * Presentation only — every label on an entry was worded by the server, from the enum that defines the kind.
 */
const ICONS: Record<
    TimelineEntryKind,
    ComponentType<{ className?: string }>
> = {
    started: PlayCircle,
    change: PenLine,
    experiment: TestTubes,
    playtest: FlaskConical,
    decision: Scale,
    completed: CheckCircle2,
    cancelled: CircleSlash,
};

/**
 * A design cycle as it happened, on one axis.
 *
 * The module's primary interaction. Changes, experiments, playtests, decisions and the cycle's own boundaries
 * share one line, because "the decision came four days after the playtest and two hours before the cycle
 * closed" is a fact about how a studio works and it is only visible when all of it is on one axis.
 *
 * The lifecycle entries are drawn differently — filled marker, no left border below — because they are the
 * ends of the line rather than points on it. Whether an entry is one of those comes from the server rather
 * than from a list kept here.
 *
 * Entries with no moment sit at the end rather than the start. A change typed up after the fact belongs in
 * the account, but putting it first would place the epilogue before the game.
 */
export default function IterationTimeline({
    timeline,
    workspace,
    game,
}: IterationTimelineProps) {
    const { t, choice } = useTranslation();
    const { formatTime } = useFormatters();

    if (timeline.is_empty) {
        return (
            <p
                className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                data-test="timeline-empty"
            >
                {t(
                    'Nothing recorded yet. Once the iteration starts, changes, experiments, playtests and decisions appear here in the order they happened.',
                )}
            </p>
        );
    }

    return (
        <ol className="space-y-3" data-test="iteration-timeline">
            {timeline.entries.map((entry) => {
                const Icon = ICONS[entry.kind];
                const at = entry.at ? formatTime(entry.at) : t('Later');

                return (
                    <li
                        key={`${entry.kind}-${entry.id}`}
                        className="flex gap-3"
                    >
                        <div className="w-14 shrink-0 pt-0.5 text-end text-xs text-muted-foreground tabular-nums">
                            {at}
                        </div>

                        <div
                            className={
                                entry.is_lifecycle
                                    ? 'min-w-0 flex-1 space-y-1 pb-1'
                                    : 'min-w-0 flex-1 space-y-1 border-s ps-3 pb-1'
                            }
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
                                    <Icon className="size-3" />
                                    {entry.kind_label}
                                </span>

                                {entry.badge && (
                                    <Badge variant="outline">
                                        {entry.badge}
                                    </Badge>
                                )}
                            </div>

                            <p className="text-sm font-medium" dir="auto">
                                {entry.kind === 'playtest' &&
                                entry.reference ? (
                                    <Link
                                        href={playtests.show.url({
                                            workspace,
                                            game,
                                            playtest: entry.reference,
                                        })}
                                        className="hover:underline"
                                        data-test={`timeline-playtest-${entry.reference}`}
                                    >
                                        {entry.title}
                                    </Link>
                                ) : (
                                    entry.title
                                )}
                            </p>

                            {entry.body && (
                                <p
                                    className="text-sm text-muted-foreground"
                                    dir="auto"
                                >
                                    {entry.body}
                                </p>
                            )}

                            {entry.counts && (
                                <p className="text-xs text-muted-foreground">
                                    {choice(
                                        ':count observation|:count observations',
                                        entry.counts.observations ?? 0,
                                    )}
                                    {' · '}
                                    {choice(
                                        ':count comment|:count comments',
                                        entry.counts.feedback ?? 0,
                                    )}
                                </p>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
