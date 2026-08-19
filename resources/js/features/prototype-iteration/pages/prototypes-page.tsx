import { Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import Heading from '@/components/heading';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Game, GameVersion } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import CreatePrototypeDialog from '../components/create-prototype-dialog';
import PrototypeList from '../components/prototype-list';
import { usePrototypeFilters } from '../hooks/use-prototype-filters';
import type {
    PrototypeCard,
    PrototypeFilters,
    PrototypeOptions,
    PrototypeStatus,
    PrototypeType,
} from '../types/prototype-iteration';

type PrototypesPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    prototypes: { data: PrototypeCard[] };
    versions: { data: GameVersion[] };
    filters: PrototypeFilters;
    options: PrototypeOptions;
    can: { create: boolean };
};

/**
 * The prototypes of a game.
 *
 * The list arrives already scoped to the game, which is itself scoped to the workspace, so there is nothing to
 * filter for security here — the controls narrow a list the caller can already see, and they do it by asking
 * the server again rather than by sorting an array, which keeps the filters in the URL where they can be
 * shared and bookmarked.
 *
 * The "any" sentinels exist because a Radix select cannot hold an empty string as a value; they are translated
 * back to null before they reach the URL.
 */
const ANY_STATUS = 'any-status';
const ANY_TYPE = 'any-type';

export default function PrototypesPage({
    workspace: { data: workspace },
    game: { data: game },
    prototypes: { data },
    versions: { data: versions },
    filters: initialFilters,
    options,
    can,
}: PrototypesPageProps) {
    const { t } = useTranslation();
    const { prototypes, filters, isFiltered, setSearch, setStatus, setType } =
        usePrototypeFilters(workspace.slug, game.slug, data, initialFilters);

    return (
        <>
            <Head title={t('Prototypes · :game', { game: game.name })} />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title={t('Prototypes')}
                        description={t(
                            'What this game has actually been built as, and how often',
                        )}
                    />

                    {can.create && (
                        <CreatePrototypeDialog
                            workspace={workspace.slug}
                            game={game.slug}
                            versions={versions}
                            options={options}
                        />
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative min-w-56 flex-1">
                        <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />

                        <Input
                            value={filters.search ?? ''}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Search prototypes')}
                            className="ps-9"
                            aria-label={t('Search prototypes')}
                            data-test="prototype-search"
                        />
                    </div>

                    <Select
                        value={filters.status ?? ANY_STATUS}
                        onValueChange={(value) =>
                            setStatus(
                                value === ANY_STATUS
                                    ? null
                                    : (value as PrototypeStatus),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label={t('Filter by status')}
                            data-test="prototype-status-filter"
                        >
                            <SelectValue placeholder={t('Any status')} />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value={ANY_STATUS}>
                                {t('Any status')}
                            </SelectItem>

                            {options.statuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.type ?? ANY_TYPE}
                        onValueChange={(value) =>
                            setType(
                                value === ANY_TYPE
                                    ? null
                                    : (value as PrototypeType),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label={t('Filter by kind')}
                            data-test="prototype-type-filter"
                        >
                            <SelectValue placeholder={t('Any kind')} />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value={ANY_TYPE}>
                                {t('Any kind')}
                            </SelectItem>

                            {options.types.map((type) => (
                                <SelectItem key={type.value} value={type.value}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <PrototypeList
                    prototypes={prototypes}
                    workspace={workspace.slug}
                    game={game.slug}
                    isFiltered={isFiltered}
                />
            </div>
        </>
    );
}
