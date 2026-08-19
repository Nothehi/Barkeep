import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import prototypes from '@/routes/prototypes';
import type {
    PrototypeCard,
    PrototypeFilters,
    PrototypeStatus,
    PrototypeType,
} from '../types/prototype-iteration';

export type UsePrototypeFiltersResult = {
    prototypes: PrototypeCard[];
    filters: PrototypeFilters;
    isFiltered: boolean;
    setSearch: (search: string) => void;
    setStatus: (status: PrototypeStatus | null) => void;
    setType: (type: PrototypeType | null) => void;
    clearFilters: () => void;
};

/**
 * The prototypes list and the filters applied to it.
 *
 * Filtering is a server round trip rather than a client-side sort of an array the page already holds. That is
 * deliberate: the list is scoped and ordered server side, so a filtered view has to come from the same query
 * that produced the unfiltered one — and it keeps the filters in the URL, which makes a filtered list a thing
 * somebody can bookmark or share.
 *
 * The visit replaces history rather than pushing to it, so typing into the search box does not bury the
 * previous page under a stack of keystrokes.
 */
export function usePrototypeFilters(
    workspace: string,
    game: string,
    initial: PrototypeCard[],
    initialFilters: PrototypeFilters,
): UsePrototypeFiltersResult {
    const [filters, setFilters] = useState<PrototypeFilters>(initialFilters);

    const visit = useCallback(
        (next: PrototypeFilters) => {
            setFilters(next);

            router.get(
                prototypes.index.url({ workspace, game }),
                {
                    ...(next.search ? { search: next.search } : {}),
                    ...(next.status ? { status: next.status } : {}),
                    ...(next.type ? { type: next.type } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['prototypes', 'filters'],
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
        (status: PrototypeStatus | null) => visit({ ...filters, status }),
        [filters, visit],
    );

    const setType = useCallback(
        (type: PrototypeType | null) => visit({ ...filters, type }),
        [filters, visit],
    );

    const clearFilters = useCallback(
        () => visit({ search: null, status: null, type: null }),
        [visit],
    );

    const isFiltered = useMemo(
        () => Boolean(filters.search || filters.status || filters.type),
        [filters],
    );

    return {
        prototypes: initial,
        filters,
        isFiltered,
        setSearch,
        setStatus,
        setType,
        clearFilters,
    };
}
