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
import CreatePlaytestDialog from '../components/create-playtest-dialog';
import PlaytestList from '../components/playtest-list';
import { usePlaytests } from '../hooks/use-playtests';
import type {
    PlaytestFilters,
    PlaytestOptions,
    PlaytestSummary,
} from '../types/playtest';

type PlaytestsPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    playtests: { data: PlaytestSummary[] };
    versions: { data: GameVersion[] };
    filters: PlaytestFilters;
    options: PlaytestOptions;
    can: { create: boolean };
};

/**
 * The playtests of a game.
 *
 * The list arrives already scoped to the game, which is itself scoped to the
 * workspace, so there is nothing to filter for security here — the controls
 * narrow a list the caller can already see, and they do it by asking the
 * server again rather than by sorting an array, which keeps the filters in the
 * URL where they can be shared and bookmarked.
 *
 * The "any status" sentinel exists because a Radix select cannot hold an empty
 * string as a value; it is translated back to null before it reaches the URL.
 */
const ANY_STATUS = 'any-status';

export default function PlaytestsPage({
    workspace: { data: workspace },
    game: { data: game },
    playtests: { data },
    versions: { data: versions },
    filters: initialFilters,
    options,
    can,
}: PlaytestsPageProps) {
    const { playtests, filters, isFiltered, setSearch, setStatus } =
        usePlaytests(workspace.slug, game.slug, data, initialFilters);

    return (
        <>
            <Head title={`Playtests · ${game.name}`} />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Playtests"
                        description="What this game has been tested for, and what came of it"
                    />

                    {can.create && (
                        <CreatePlaytestDialog
                            workspace={workspace.slug}
                            game={game.slug}
                            versions={versions}
                        />
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative min-w-56 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                        <Input
                            value={filters.search ?? ''}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search playtests"
                            className="pl-9"
                            aria-label="Search playtests"
                            data-test="playtest-search"
                        />
                    </div>

                    <Select
                        value={filters.status ?? ANY_STATUS}
                        onValueChange={(value) =>
                            setStatus(
                                value === ANY_STATUS
                                    ? null
                                    : (value as PlaytestFilters['status']),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label="Filter by status"
                            data-test="playtest-status-filter"
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
                </div>

                <PlaytestList
                    playtests={playtests}
                    workspace={workspace.slug}
                    game={game.slug}
                    isFiltered={isFiltered}
                />
            </div>
        </>
    );
}
