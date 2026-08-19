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
import CreateIterationDialog from '../components/create-iteration-dialog';
import IterationList from '../components/iteration-list';
import { useIterationFilters } from '../hooks/use-iteration-filters';
import type {
    IterationCard,
    IterationFilters,
    IterationOptions,
    IterationOutcome,
    IterationStatus,
    PrototypeVersion,
} from '../types/prototype-iteration';

type IterationsPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    iterations: { data: IterationCard[] };
    prototype_versions: { data: PrototypeVersion[] };
    versions: { data: GameVersion[] };
    filters: IterationFilters;
    options: IterationOptions;
    can: { create: boolean };
};

/**
 * The design cycles of a game.
 *
 * The outcome filter is the one that earns its place on this screen. "Show me everything that failed" is how a
 * designer finds the thread of a problem that has been resisting them for months, and it is only answerable
 * because every completed cycle was made to record an outcome.
 */
const ANY_STATUS = 'any-status';
const ANY_OUTCOME = 'any-outcome';

export default function IterationsPage({
    workspace: { data: workspace },
    game: { data: game },
    iterations: { data },
    prototype_versions: { data: prototypeVersions },
    versions: { data: versions },
    filters: initialFilters,
    options,
    can,
}: IterationsPageProps) {
    const { t } = useTranslation();
    const {
        iterations,
        filters,
        isFiltered,
        setSearch,
        setStatus,
        setOutcome,
    } = useIterationFilters(workspace.slug, game.slug, data, initialFilters);

    return (
        <>
            <Head title={t('Iterations · :game', { game: game.name })} />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title={t('Iterations')}
                        description={t(
                            'What was changed, what it was tested against, and what came of it',
                        )}
                    />

                    {can.create && (
                        <CreateIterationDialog
                            workspace={workspace.slug}
                            game={game.slug}
                            versions={versions}
                            prototypeVersions={prototypeVersions}
                        />
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative min-w-56 flex-1">
                        <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />

                        <Input
                            value={filters.search ?? ''}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Search iterations')}
                            className="ps-9"
                            aria-label={t('Search iterations')}
                            data-test="iteration-search"
                        />
                    </div>

                    <Select
                        value={filters.status ?? ANY_STATUS}
                        onValueChange={(value) =>
                            setStatus(
                                value === ANY_STATUS
                                    ? null
                                    : (value as IterationStatus),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label={t('Filter by status')}
                            data-test="iteration-status-filter"
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
                        value={filters.outcome ?? ANY_OUTCOME}
                        onValueChange={(value) =>
                            setOutcome(
                                value === ANY_OUTCOME
                                    ? null
                                    : (value as IterationOutcome),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label={t('Filter by outcome')}
                            data-test="iteration-outcome-filter"
                        >
                            <SelectValue placeholder={t('Any outcome')} />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value={ANY_OUTCOME}>
                                {t('Any outcome')}
                            </SelectItem>

                            {options.outcomes.map((outcome) => (
                                <SelectItem
                                    key={outcome.value}
                                    value={outcome.value}
                                >
                                    {outcome.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <IterationList
                    iterations={iterations}
                    workspace={workspace.slug}
                    game={game.slug}
                    isFiltered={isFiltered}
                />
            </div>
        </>
    );
}
