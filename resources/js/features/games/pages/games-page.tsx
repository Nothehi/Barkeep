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
import type { Workspace } from '@/features/workspaces';
import CreateGameDialog from '../components/create-game-dialog';
import GameList from '../components/game-list';
import { useGames } from '../hooks/use-games';
import type { GameFilters, GameOptions, GameSummary } from '../types/game';

type GamesPageProps = {
    workspace: { data: Workspace };
    games: { data: GameSummary[] };
    filters: GameFilters;
    options: GameOptions;
    can: { create: boolean };
};

/**
 * The games in a workspace.
 *
 * The list arrives already scoped to the workspace, so there is nothing to
 * filter for security here — the controls below narrow a list the caller can
 * already see, and they do it by asking the server again rather than by
 * sorting an array, which keeps the filters in the URL where they can be
 * shared and bookmarked.
 *
 * The "any status" and "any phase" sentinels exist because a Radix select
 * cannot hold an empty string as a value; they are translated back to null
 * before they reach the URL.
 */
const ANY_STATUS = 'any-status';
const ANY_PHASE = 'any-phase';

export default function GamesPage({
    workspace: { data: workspace },
    games: { data },
    filters: initialFilters,
    options,
    can,
}: GamesPageProps) {
    const {
        games,
        filters,
        isFiltered,
        setSearch,
        setStatus,
        setDesignPhase,
        clearFilters,
    } = useGames(workspace.slug, data, initialFilters);

    return (
        <>
            <Head title={`Games · ${workspace.name}`} />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Games"
                        description={`The board games being designed in ${workspace.name}`}
                    />

                    {can.create && (
                        <CreateGameDialog
                            workspace={workspace.slug}
                            options={options}
                        />
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative min-w-56 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                        <Input
                            value={filters.search ?? ''}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search games"
                            className="pl-9"
                            aria-label="Search games"
                            data-test="game-search"
                        />
                    </div>

                    <Select
                        value={filters.status ?? ANY_STATUS}
                        onValueChange={(value) =>
                            setStatus(
                                value === ANY_STATUS
                                    ? null
                                    : (value as GameFilters['status']),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label="Filter by status"
                            data-test="game-status-filter"
                        >
                            <SelectValue placeholder="Any status" />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value={ANY_STATUS}>
                                Any status
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
                        value={filters.design_phase ?? ANY_PHASE}
                        onValueChange={(value) =>
                            setDesignPhase(
                                value === ANY_PHASE
                                    ? null
                                    : (value as GameFilters['design_phase']),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-48"
                            aria-label="Filter by design phase"
                            data-test="game-phase-filter"
                        >
                            <SelectValue placeholder="Any phase" />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem value={ANY_PHASE}>Any phase</SelectItem>

                            {options.design_phases.map((phase) => (
                                <SelectItem
                                    key={phase.value}
                                    value={phase.value}
                                >
                                    {phase.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <GameList
                    games={games}
                    workspace={workspace.slug}
                    isFiltered={isFiltered}
                    onClearFilters={clearFilters}
                />
            </div>
        </>
    );
}
