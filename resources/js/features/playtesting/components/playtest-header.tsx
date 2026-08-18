import { Link } from '@inertiajs/react';
import { ArrowLeft, GitBranch } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
import { useTranslation } from '@/lib/i18n';
import playtests from '@/routes/playtests';
import { usePlaytest } from '../hooks/use-playtest';
import type { Playtest } from '../types/playtest';
import PlaytestStatusBadge from './playtest-status-badge';

type PlaytestHeaderProps = {
    playtest: Playtest;
    workspace: string;
    game: string;
};

/**
 * The heading and actions for one playtest.
 *
 * The lifecycle buttons are drawn from `available_transitions`, which the
 * server derives from the domain's transition matrix per playtest. The screen
 * renders what it is given; it does not know which moves are legal, so it
 * cannot get them wrong when the matrix changes.
 *
 * Completing opens a dialog with a conclusion field, because that is the
 * moment somebody has an answer in their head. It is optional, and writable
 * afterwards, so nobody is blocked from closing an investigation by not having
 * time to write it up.
 */
export default function PlaytestHeader({
    playtest,
    workspace,
    game,
}: PlaytestHeaderProps) {
    const { t } = useTranslation();
    const { complete, cancel, processing } = usePlaytest(
        workspace,
        game,
        playtest,
    );
    const [completing, setCompleting] = useState(false);
    const [conclusion, setConclusion] = useState('');

    const act = (status: string) => {
        if (status === 'completed') {
            setCompleting(true);

            return;
        }

        cancel();
    };

    return (
        <header className="space-y-4">
            <Button variant="ghost" size="sm" asChild className="-ms-2">
                <Link href={playtests.index.url({ workspace, game })}>
                    <ArrowLeft className="size-4 rtl:rotate-180" />
                    {t('All playtests')}
                </Link>
            </Button>

            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 space-y-2">
                    <h1
                        className="truncate text-xl font-semibold tracking-tight"
                        dir="auto"
                    >
                        {playtest.title}
                    </h1>

                    <div className="flex flex-wrap items-center gap-2">
                        <PlaytestStatusBadge
                            status={playtest.status}
                            label={playtest.status_label}
                        />

                        {playtest.version && (
                            <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                                <GitBranch className="size-3.5" />
                                {t('Testing :version', {
                                    version: playtest.version.label,
                                })}
                                {playtest.version.name
                                    ? ` · ${playtest.version.name}`
                                    : ''}
                            </span>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {playtest.available_transitions.map((transition) => (
                        <Button
                            key={transition.status}
                            variant={
                                transition.status === 'completed'
                                    ? 'default'
                                    : 'outline'
                            }
                            size="sm"
                            disabled={processing}
                            onClick={() => act(transition.status)}
                            data-test={`playtest-transition-${transition.status}`}
                        >
                            {transition.label}
                        </Button>
                    ))}
                </div>
            </div>

            <Dialog open={completing} onOpenChange={setCompleting}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Complete this playtest')}</DialogTitle>
                        <DialogDescription>
                            {t(
                                'Say what you concluded, if you know yet. The plan becomes read-only, but you can still write this up later.',
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <Textarea
                        value={conclusion}
                        onChange={(event) => setConclusion(event.target.value)}
                        placeholder={t(
                            'The hypothesis was partially supported. The first-player advantage exists but is smaller than expected.',
                        )}
                        rows={4}
                        data-test="playtest-conclusion-input"
                    />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setCompleting(false)}
                        >
                            {t('Cancel')}
                        </Button>

                        <Button
                            type="button"
                            disabled={processing}
                            onClick={() => {
                                complete(conclusion);
                                setCompleting(false);
                            }}
                            data-test="confirm-complete-playtest-button"
                        >
                            {processing && <Spinner />}
                            {t('Complete playtest')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </header>
    );
}
