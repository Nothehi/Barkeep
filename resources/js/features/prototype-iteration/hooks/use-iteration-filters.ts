import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import iterations from '@/routes/iterations';
import type {
    IterationCard,
    IterationFilters,
    IterationOutcome,
    IterationStatus,
} from '../types/prototype-iteration';

export type UseIterationFiltersResult = {
    iterations: IterationCard[];
    filters: IterationFilters;
    isFiltered: boolean;
    setSearch: (search: string) => void;
    setStatus: (status: IterationStatus | null) => void;
    setOutcome: (outcome: IterationOutcome | null) => void;
    setPrototype: (prototype: string | null) => void;
    clearFilters: () => void;
};

/**
 * The design cycles list and the filters applied to it.
 *
 * The outcome filter is the one designers actually reach for — "show me everything that failed" is how
 * somebody finds the thread of a problem that has been resisting them for months. It only ever matches
 * completed cycles, since nothing else has an outcome, which is why it is a separate control from the status
 * rather than one combined state picker.
 */
export function useIterationFilters(
    workspace: string,
    game: string,
    initial: IterationCard[],
    initialFilters: IterationFilters,
): UseIterationFiltersResult {
    const [filters, setFilters] = useState<IterationFilters>(initialFilters);

    const visit = useCallback(
        (next: IterationFilters) => {
            setFilters(next);

            router.get(
                iterations.index.url({ workspace, game }),
                {
                    ...(next.search ? { search: next.search } : {}),
                    ...(next.status ? { status: next.status } : {}),
                    ...(next.outcome ? { outcome: next.outcome } : {}),
                    ...(next.prototype ? { prototype: next.prototype } : {}),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['iterations', 'filters'],
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
        (status: IterationStatus | null) => visit({ ...filters, status }),
        [filters, visit],
    );

    const setOutcome = useCallback(
        (outcome: IterationOutcome | null) => visit({ ...filters, outcome }),
        [filters, visit],
    );

    const setPrototype = useCallback(
        (prototype: string | null) => visit({ ...filters, prototype }),
        [filters, visit],
    );

    const clearFilters = useCallback(
        () =>
            visit({
                search: null,
                status: null,
                outcome: null,
                prototype: null,
            }),
        [visit],
    );

    const isFiltered = useMemo(
        () =>
            Boolean(
                filters.search ||
                filters.status ||
                filters.outcome ||
                filters.prototype,
            ),
        [filters],
    );

    return {
        iterations: initial,
        filters,
        isFiltered,
        setSearch,
        setStatus,
        setOutcome,
        setPrototype,
        clearFilters,
    };
}
