import { Repeat } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import type { IterationCard as IterationCardData } from '../types/prototype-iteration';
import IterationCard from './iteration-card';

type IterationListProps = {
    iterations: IterationCardData[];
    workspace: string;
    game: string;
    isFiltered: boolean;
};

/**
 * The design cycles of a game, newest first.
 *
 * Newest first because the cycle somebody wants is the one they are in. A design history is read backwards
 * from the present when you are working and forwards from the start when you are learning — the second is
 * what an individual cycle's timeline is for.
 */
export default function IterationList({
    iterations,
    workspace,
    game,
    isFiltered,
}: IterationListProps) {
    const { t } = useTranslation();

    if (iterations.length === 0) {
        return (
            <div
                className="rounded-lg border border-dashed px-6 py-12 text-center"
                data-test="iterations-empty"
            >
                <Repeat className="mx-auto size-8 text-muted-foreground" />

                <p className="mt-3 text-sm font-medium">
                    {isFiltered
                        ? t('No iterations match those filters')
                        : t('No iterations yet')}
                </p>

                <p className="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                    {isFiltered
                        ? t('Try clearing a filter to see the rest.')
                        : t(
                              'An iteration is one turn of the design loop: change something, test it, decide what it means. Plan one to start recording why this game is the way it is.',
                          )}
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-3" data-test="iteration-list">
            {iterations.map((iteration) => (
                <IterationCard
                    key={iteration.id}
                    iteration={iteration}
                    workspace={workspace}
                    game={game}
                />
            ))}
        </div>
    );
}
