import { Link } from '@inertiajs/react';
import { EyeOff, Plus, Unlink, Users } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/lib/i18n';
import playtestRoutes from '@/routes/playtests';
import { attachPlaytest, detachPlaytest } from '../api';
import type {
    PlaytestReference,
    SelectablePlaytest,
} from '../types/prototype-iteration';

type RelatedPlaytestsProps = {
    playtests: PlaytestReference[];
    available: SelectablePlaytest[];
    workspace: string;
    game: string;
    iteration: string;
    canAttach: boolean;
};

/**
 * The playtests this cycle was tested through.
 *
 * Every figure on a row came from Playtesting at the moment this page was rendered, through the server's own
 * adapter — nothing about a playtest is stored in this module, and nothing on this screen talks to
 * Playtesting's client state either. That is why the counts here always agree with the playtest's own screen
 * instead of drifting the first time somebody adds a session.
 *
 * The picker offers the whole list and greys out what is already attached, rather than the server filtering
 * it: the screen knows which links it holds, and filtering server side would mean the adapter needing to know
 * about the iteration doing the asking.
 *
 * A playtest the reader cannot see is shown as unavailable rather than dropped, so "this cycle was judged on
 * evidence you cannot open" stays visible.
 */
export default function RelatedPlaytests({
    playtests,
    available,
    workspace,
    game,
    iteration,
    canAttach,
}: RelatedPlaytestsProps) {
    const { t, choice } = useTranslation();
    const [selected, setSelected] = useState('');

    const attached = new Set(playtests.map((playtest) => playtest.playtest_id));
    const unattached = available.filter(
        (playtest) => !attached.has(playtest.id),
    );

    const attach = () => {
        if (selected === '') {
            return;
        }

        attachPlaytest({ workspace, game }, iteration, selected, {
            onSuccess: () => setSelected(''),
        });
    };

    return (
        <Card data-test="related-playtests">
            <CardHeader>
                <CardTitle className="text-base">
                    {t('Related playtests')}
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {playtests.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed py-6 text-center text-sm text-muted-foreground"
                        data-test="playtests-empty"
                    >
                        {t('No playtests attached yet.')}
                    </p>
                ) : (
                    <ul className="space-y-2" data-test="playtest-list">
                        {playtests.map((playtest) => (
                            <li
                                key={playtest.link_id}
                                className="flex flex-wrap items-start justify-between gap-2 rounded-md border p-3"
                                data-test={`playtest-${playtest.link_id}`}
                            >
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        {playtest.is_available ? (
                                            <Link
                                                href={playtestRoutes.show.url({
                                                    workspace,
                                                    game,
                                                    playtest:
                                                        playtest.playtest_id,
                                                })}
                                                className="text-sm font-medium hover:underline"
                                                dir="auto"
                                            >
                                                {playtest.title}
                                            </Link>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                                                <EyeOff className="size-3" />
                                                {playtest.title}
                                            </span>
                                        )}

                                        <Badge variant="outline">
                                            {playtest.status_label}
                                        </Badge>
                                    </div>

                                    {playtest.is_available && (
                                        <p className="flex flex-wrap items-center gap-x-3 text-xs text-muted-foreground">
                                            <span className="inline-flex items-center gap-1">
                                                <Users className="size-3" />
                                                {choice(
                                                    ':count player|:count players',
                                                    playtest.participants_count,
                                                )}
                                            </span>

                                            <span>
                                                {choice(
                                                    ':count session|:count sessions',
                                                    playtest.sessions_count,
                                                )}
                                            </span>

                                            <span>
                                                {choice(
                                                    ':count observation|:count observations',
                                                    playtest.observations_count,
                                                )}
                                            </span>

                                            <span>
                                                {choice(
                                                    ':count comment|:count comments',
                                                    playtest.feedback_count,
                                                )}
                                            </span>
                                        </p>
                                    )}
                                </div>

                                {canAttach && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            detachPlaytest(
                                                { workspace, game },
                                                iteration,
                                                playtest.link_id,
                                            )
                                        }
                                        aria-label={t('Detach playtest')}
                                        data-test={`detach-playtest-${playtest.link_id}`}
                                    >
                                        <Unlink className="size-3.5" />
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                {canAttach &&
                    (unattached.length === 0 ? (
                        <p
                            className="text-xs text-muted-foreground"
                            data-test="no-playtests-to-attach"
                        >
                            {available.length === 0
                                ? t('This game has no playtests yet.')
                                : t(
                                      'Every playtest of this game is already attached.',
                                  )}
                        </p>
                    ) : (
                        <div className="flex flex-wrap items-end gap-2">
                            <Select
                                value={selected}
                                onValueChange={setSelected}
                            >
                                <SelectTrigger
                                    className="min-w-56 flex-1"
                                    aria-label={t('Choose a playtest')}
                                    data-test="attach-playtest-picker"
                                >
                                    <SelectValue
                                        placeholder={t('Choose a playtest')}
                                    />
                                </SelectTrigger>

                                <SelectContent>
                                    {unattached.map((playtest) => (
                                        <SelectItem
                                            key={playtest.id}
                                            value={playtest.id}
                                        >
                                            {playtest.title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Button
                                onClick={attach}
                                disabled={selected === ''}
                                data-test="attach-playtest-button"
                            >
                                <Plus className="size-4" />
                                {t('Attach playtest')}
                            </Button>
                        </div>
                    ))}
            </CardContent>
        </Card>
    );
}
