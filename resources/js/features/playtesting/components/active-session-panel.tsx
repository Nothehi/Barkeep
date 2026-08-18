import { Eye, MessageSquare, Play, Square, Users } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { formatElapsed } from '../hooks/use-elapsed-time';
import type { UseSessionResult } from '../hooks/use-session';
import SessionStatusBadge from './session-status-badge';

type ActiveSessionPanelProps = {
    session: UseSessionResult;
    participantCount: number;
    observationCount: number;
    feedbackCount: number;
};

/**
 * The control panel at the top of a session screen.
 *
 * This is the part of the module somebody uses while standing at a table with
 * four people waiting, so it does exactly three things: shows how long the
 * session has been running, shows how much has been recorded, and offers the
 * one button that matters right now.
 *
 * The clock is derived from the server's `started_at` rather than counted up
 * from page load, so refreshing does not restart it and two people watching
 * the same session see the same number.
 *
 * Ending a session opens a dialog with an outcome field, because that is the
 * moment somebody has an answer in their head. It is optional — ending happens
 * while people are standing up and putting the box away, and a dialog that
 * demanded a write-up first would be a dialog that gets dismissed, after which
 * the session never gets ended at all.
 */
export default function ActiveSessionPanel({
    session,
    participantCount,
    observationCount,
    feedbackCount,
}: ActiveSessionPanelProps) {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();
    const [ending, setEnding] = useState(false);
    const [outcome, setOutcome] = useState('');

    const { permissions, isRunning, elapsed, processing } = session;

    return (
        <Card data-test="active-session-panel">
            <CardContent className="flex flex-wrap items-center justify-between gap-6">
                <div className="space-y-2">
                    <SessionStatusBadge
                        status={session.session.status}
                        label={session.session.status_label}
                    />

                    <p
                        className="text-3xl font-semibold tabular-nums"
                        data-test="session-clock"
                    >
                        {isRunning && elapsed !== null
                            ? formatElapsed(elapsed)
                            : (session.session.duration_label ?? '—')}
                    </p>
                </div>

                <dl className="flex flex-wrap gap-6 text-sm">
                    <div className="space-y-1">
                        <dt className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <Users className="size-3" />
                            {t('Participants')}
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatNumber(participantCount)}
                        </dd>
                    </div>

                    <div className="space-y-1">
                        <dt className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <Eye className="size-3" />
                            {t('Observations')}
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatNumber(observationCount)}
                        </dd>
                    </div>

                    <div className="space-y-1">
                        <dt className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <MessageSquare className="size-3" />
                            {t('Feedback')}
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatNumber(feedbackCount)}
                        </dd>
                    </div>
                </dl>

                <div className="flex flex-wrap items-center gap-2">
                    {permissions.canStart && !isRunning && (
                        <Button
                            size="lg"
                            disabled={processing}
                            onClick={session.start}
                            data-test="start-session-button"
                        >
                            {processing ? (
                                <Spinner />
                            ) : (
                                <Play className="size-4" />
                            )}
                            {t('Start session')}
                        </Button>
                    )}

                    {permissions.canComplete && isRunning && (
                        <Button
                            size="lg"
                            disabled={processing}
                            onClick={() => setEnding(true)}
                            data-test="end-session-button"
                        >
                            <Square className="size-4" />
                            {t('End session')}
                        </Button>
                    )}

                    {permissions.canCancel && (
                        <Button
                            variant="outline"
                            size="lg"
                            disabled={processing}
                            onClick={session.cancel}
                            data-test="cancel-session-button"
                        >
                            {t('Cancel')}
                        </Button>
                    )}
                </div>
            </CardContent>

            <Dialog open={ending} onOpenChange={setEnding}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('End this session')}</DialogTitle>
                        <DialogDescription>
                            {t(
                                'Say what this sitting settled, if you know. Nothing more can be added afterwards, which is what makes the record datable.',
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <Textarea
                        value={outcome}
                        onChange={(event) => setOutcome(event.target.value)}
                        placeholder={t(
                            'The first-player advantage showed up again, but the catch-up bonus covered most of it.',
                        )}
                        rows={4}
                        data-test="session-outcome-input"
                    />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setEnding(false)}
                        >
                            {t('Keep going')}
                        </Button>

                        <Button
                            type="button"
                            disabled={processing}
                            onClick={() => {
                                session.complete({ outcome, notes: '' });
                                setEnding(false);
                            }}
                            data-test="confirm-end-session-button"
                        >
                            {processing && <Spinner />}
                            {t('End session')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}
