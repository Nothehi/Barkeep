import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { usePlaytestSessions } from '../hooks/use-playtest-sessions';
import type { Playtest, PlaytestSession } from '../types/playtest';
import SessionCard from './session-card';

type SessionListProps = {
    playtest: Playtest;
    sessions: PlaytestSession[];
    workspace: string;
    game: string;
};

/**
 * A playtest's sittings, earliest first.
 *
 * Scheduling one takes a single press with no form. The common case is a
 * designer about to run a session in the next thirty seconds, and a dialog
 * asking where they are sitting would be a dialog that gets abandoned — after
 * which the session gets run without being recorded. Location and notes are
 * editable on the session itself once it exists.
 */
export default function SessionList({
    playtest,
    sessions,
    workspace,
    game,
}: SessionListProps) {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();
    const form = usePlaytestSessions(workspace, game, playtest.id, sessions);

    return (
        <section className="space-y-4" data-test="session-list">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold">{t('Sessions')}</h2>

                    <p className="text-sm text-muted-foreground">
                        {sessions.length === 0
                            ? t(
                                  'Each session is one group playing this version.',
                              )
                            : t(':completed of :total completed', {
                                  completed: formatNumber(form.completedCount),
                                  total: formatNumber(sessions.length),
                              })}
                    </p>
                </div>

                {playtest.permissions.canCreateSession && (
                    <Button
                        size="sm"
                        disabled={form.processing}
                        onClick={form.submit}
                        data-test="create-session-button"
                    >
                        {form.processing ? (
                            <Spinner />
                        ) : (
                            <Plus className="size-4" />
                        )}
                        {t('New session')}
                    </Button>
                )}
            </div>

            {sessions.length === 0 ? (
                <p
                    className="rounded-lg border border-dashed py-10 text-center text-sm text-muted-foreground"
                    data-test="sessions-empty"
                >
                    {t(
                        'No sessions yet. Start one when you have people at a table.',
                    )}
                </p>
            ) : (
                <div className="grid gap-3 sm:grid-cols-2">
                    {sessions.map((session, index) => (
                        <SessionCard
                            key={session.id}
                            session={session}
                            workspace={workspace}
                            game={game}
                            playtest={playtest.id}
                            index={index}
                        />
                    ))}
                </div>
            )}
        </section>
    );
}
