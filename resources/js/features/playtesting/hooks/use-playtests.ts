import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import playtests from '@/routes/playtests';
import type {
    PlaytestFilters,
    PlaytestStatus,
    PlaytestSummary,
} from '../types/playtest';

export type UsePlaytestsResult = {
    playtests: PlaytestSummary[];
    filters: PlaytestFilters;
    isFiltered: boolean;
    setSearch: (search: string) => void;
    setStatus: (status: PlaytestStatus | null) => void;
    clearFilters: () => void;
};

/**
 * The playtests list and the filters applied to it.
 *
 * Filtering is a server round trip rather than a client-side sort of an array
 * the page already holds. That is deliberate: the list is scoped and ordered
 * server side, so a filtered view has to come from the same query that
 * produced the unfiltered one — and it keeps the filters in the URL, which
 * makes a filtered list a thing somebody can bookmark or share.
 *
 * The visit replaces history rather than pushing to it, so typing into the
 * search box does not bury the previous page under a stack of keystrokes.
 */
export function usePlaytests(
    workspace: string,
    game: string,
    initial: PlaytestSummary[],
    initialFilters: PlaytestFilters,
): UsePlaytestsResult {
    const [filters, setFilters] = useState<PlaytestFilters>(initialFilters);

    const visit = useCallback(
        (next: PlaytestFilters) => {
            setFilters(next);

            router.get(
                playtests.index.url({ workspace, game }),
                {
                    ...(next.search ? { search: next.search } : {}),
                    ...(next.status ? { status: next.status } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['playtests', 'filters'],
                },
            );
        },
        [workspace, game],
    );

    const setSearch = useCallback(
        (search: string) => visit({ ...filters, search: search || null }),
        [filters, visit],
    );

    const setStatus = useCallback(
        (status: PlaytestStatus | null) => visit({ ...filters, status }),
        [filters, visit],
    );

    const clearFilters = useCallback(
        () => visit({ search: null, status: null }),
        [visit],
    );

    const isFiltered = useMemo(
        () => Boolean(filters.search || filters.status),
        [filters],
    );

    return {
        playtests: initial,
        filters,
        isFiltered,
        setSearch,
        setStatus,
        clearFilters,
    };
}
