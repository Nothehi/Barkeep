import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { User } from '@/features/auth';
import type { Game } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import playtests from '@/routes/playtests';
import ActiveSessionPanel from '../components/active-session-panel';
import FeedbackList from '../components/feedback-list';
import ObservationList from '../components/observation-list';
import ParticipantList from '../components/participant-list';
import SessionTimeline from '../components/session-timeline';
import { useSession } from '../hooks/use-session';
import type {
    Feedback,
    Observation,
    Participant,
    Playtest,
    PlaytestOptions,
    PlaytestSession,
} from '../types/playtest';

type SessionPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    playtest: { data: Playtest };
    session: { data: PlaytestSession };
    participants: { data: Participant[] };
    observations: { data: Observation[] };
    feedback: { data: Feedback[] };
    teammates: { data: User[] };
    options: PlaytestOptions;
};

/**
 * One sitting of a playtest, and the screen somebody actually runs it from.
 *
 * This is the only page in the platform used while something else is happening
 * in the room, and the whole layout is arranged around that. Everything it
 * needs arrives with the page rather than being fetched, so there is never a
 * spinner between a designer and a thought they are about to forget.
 *
 * The order is deliberate. The panel with the clock and the one button that
 * matters is at the top; the two fast-entry forms are next, because during a
 * session that is all anybody touches; the timeline — the account of what
 * happened, which is what this page becomes afterwards — sits below them.
 *
 * The forms disappear on their own once the session ends, because the server
 * stops granting the abilities they depend on. Nothing here decides that: it
 * renders the permission map, so what is on screen and what the server would
 * accept cannot drift apart.
 */
export default function SessionPage({
    workspace: { data: workspace },
    game: { data: game },
    playtest: { data: playtest },
    session: { data: sessionData },
    participants: { data: participants },
    observations: { data: observations },
    feedback: { data: feedback },
    teammates: { data: teammates },
    options,
}: SessionPageProps) {
    const { t } = useTranslation();
    const session = useSession(
        workspace.slug,
        game.slug,
        playtest.id,
        sessionData,
    );

    return (
        <>
            <Head
                title={t('Session · :playtest', { playtest: playtest.title })}
            />

            <div className="space-y-6 px-4 py-6">
                <div className="space-y-2">
                    <Button variant="ghost" size="sm" asChild className="-ms-2">
                        <Link
                            href={playtests.show.url({
                                workspace: workspace.slug,
                                game: game.slug,
                                playtest: playtest.id,
                            })}
                        >
                            <ArrowLeft className="size-4 rtl:rotate-180" />
                            <span dir="auto">{playtest.title}</span>
                        </Link>
                    </Button>

                    {sessionData.location && (
                        <p className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                            <MapPin className="size-3.5" />
                            {sessionData.location}
                        </p>
                    )}
                </div>

                <ActiveSessionPanel
                    session={session}
                    participantCount={participants.length}
                    observationCount={observations.length}
                    feedbackCount={feedback.length}
                />

                <ParticipantList
                    session={sessionData}
                    participants={participants}
                    teammates={teammates}
                    options={options}
                    workspace={workspace.slug}
                    game={game.slug}
                    playtest={playtest.id}
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <ObservationList
                        session={sessionData}
                        observations={observations}
                        participants={participants}
                        options={options}
                        workspace={workspace.slug}
                        game={game.slug}
                        playtest={playtest.id}
                    />

                    <FeedbackList
                        session={sessionData}
                        feedback={feedback}
                        participants={participants}
                        options={options}
                        workspace={workspace.slug}
                        game={game.slug}
                        playtest={playtest.id}
                    />
                </div>

                <section className="space-y-3">
                    <h2 className="font-semibold">
                        {t('Timeline')}{' '}
                        <span className="text-sm font-normal text-muted-foreground">
                            {t('how the session went')}
                        </span>
                    </h2>

                    <SessionTimeline
                        observations={observations}
                        feedback={feedback}
                    />
                </section>

                {(sessionData.notes || sessionData.outcome) && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {sessionData.notes && (
                            <section
                                className="space-y-1.5"
                                data-test="session-notes"
                            >
                                <h2 className="text-sm font-semibold text-muted-foreground">
                                    {t('Notes')}
                                </h2>

                                <p
                                    className="text-sm whitespace-pre-line"
                                    dir="auto"
                                >
                                    {sessionData.notes}
                                </p>
                            </section>
                        )}

                        {sessionData.outcome && (
                            <section
                                className="space-y-1.5"
                                data-test="session-outcome"
                            >
                                <h2 className="text-sm font-semibold text-muted-foreground">
                                    {t('Outcome')}
                                </h2>

                                <p
                                    className="text-sm whitespace-pre-line"
                                    dir="auto"
                                >
                                    {sessionData.outcome}
                                </p>
                            </section>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
