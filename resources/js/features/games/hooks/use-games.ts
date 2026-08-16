import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import games from '@/routes/games';
import type {
    DesignPhase,
    GameFilters,
    GameStatus,
    GameSummary,
} from '../types/game';

export type UseGamesResult = {
    games: GameSummary[];
    filters: GameFilters;
    isFiltered: boolean;
    setSearch: (search: string) => void;
    setStatus: (status: GameStatus | null) => void;
    setDesignPhase: (phase: DesignPhase | null) => void;
    clearFilters: () => void;
};

/**
 * The games list and the filters applied to it.
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
export function useGames(
    workspace: string,
    initial: GameSummary[],
    initialFilters: GameFilters,
): UseGamesResult {
    const [filters, setFilters] = useState<GameFilters>(initialFilters);

    const visit = useCallback(
        (next: GameFilters) => {
            setFilters(next);

            router.get(
                games.index.url(workspace),
                {
                    ...(next.search ? { search: next.search } : {}),
                    ...(next.status ? { status: next.status } : {}),
                    ...(next.design_phase
                        ? { design_phase: next.design_phase }
                        : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['games', 'filters'],
                },
            );
        },
        [workspace],
    );

    const setSearch = useCallback(
        (search: string) => visit({ ...filters, search: search || null }),
        [filters, visit],
    );

    const setStatus = useCallback(
        (status: GameStatus | null) => visit({ ...filters, status }),
        [filters, visit],
    );

    const setDesignPhase = useCallback(
        (design_phase: DesignPhase | null) =>
            visit({ ...filters, design_phase }),
        [filters, visit],
    );

    const clearFilters = useCallback(
        () => visit({ search: null, status: null, design_phase: null }),
        [visit],
    );

    const isFiltered = useMemo(
        () => Boolean(filters.search || filters.status || filters.design_phase),
        [filters],
    );

    return {
        games: initial,
        filters,
        isFiltered,
        setSearch,
        setStatus,
        setDesignPhase,
        clearFilters,
    };
}
