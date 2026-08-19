import { Boxes } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import type { PrototypeCard as PrototypeCardData } from '../types/prototype-iteration';
import PrototypeCard from './prototype-card';

type PrototypeListProps = {
    prototypes: PrototypeCardData[];
    workspace: string;
    game: string;
    isFiltered: boolean;
};

/**
 * The prototypes of a game.
 *
 * The empty state says two different things depending on why the list is empty. "No prototypes match" is a
 * filtering problem somebody fixes by clearing a control; "no prototypes yet" is an invitation, and it says
 * what a prototype is *for*, because the distinction between a prototype and a game version is the one thing
 * a new designer here will not have guessed.
 */
export default function PrototypeList({
    prototypes,
    workspace,
    game,
    isFiltered,
}: PrototypeListProps) {
    const { t } = useTranslation();

    if (prototypes.length === 0) {
        return (
            <div
                className="rounded-lg border border-dashed px-6 py-12 text-center"
                data-test="prototypes-empty"
            >
                <Boxes className="mx-auto size-8 text-muted-foreground" />

                <p className="mt-3 text-sm font-medium">
                    {isFiltered
                        ? t('No prototypes match those filters')
                        : t('No prototypes yet')}
                </p>

                <p className="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                    {isFiltered
                        ? t('Try clearing a filter to see the rest.')
                        : t(
                              'A prototype is the buildable version of your design: the printed cards, the spreadsheet, the box of parts. Start one to record what you actually put on the table.',
                          )}
                </p>
            </div>
        );
    }

    return (
        <div
            className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
            data-test="prototype-list"
        >
            {prototypes.map((prototype) => (
                <PrototypeCard
                    key={prototype.id}
                    prototype={prototype}
                    workspace={workspace}
                    game={game}
                />
            ))}
        </div>
    );
}
