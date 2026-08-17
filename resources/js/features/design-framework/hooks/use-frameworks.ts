import { router } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';
import frameworks from '@/routes/frameworks';
import type {
    Framework,
    FrameworkFilters,
    FrameworkStatus,
} from '../types/framework';

export type UseFrameworksResult = {
    frameworks: Framework[];
    filters: FrameworkFilters;
    isFiltered: boolean;
    setSearch: (search: string) => void;
    setStatus: (status: FrameworkStatus | null) => void;
    clearFilters: () => void;
};

/**
 * The framework catalogue and the filters applied to it.
 *
 * Filtering is a server round trip rather than a client-side sort of an array
 * the page already holds, and here the reason is sharper than usual: which
 * frameworks a caller may see is a permission question — drafts are visible
 * only to the people who administer methodologies — so a filtered list has to
 * come from the same query that decided the unfiltered one.
 *
 * The visit replaces history rather than pushing to it, so typing into the
 * search box does not bury the previous page under a stack of keystrokes.
 */
export function useFrameworks(
    initial: Framework[],
    initialFilters: FrameworkFilters,
): UseFrameworksResult {
    const [filters, setFilters] = useState<FrameworkFilters>(initialFilters);

    const visit = useCallback((next: FrameworkFilters) => {
        setFilters(next);

        router.get(
            frameworks.index.url(),
            {
                ...(next.search ? { search: next.search } : {}),
                ...(next.status ? { status: next.status } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['frameworks', 'filters'],
            },
        );
    }, []);

    const setSearch = useCallback(
        (search: string) => visit({ ...filters, search: search || null }),
        [filters, visit],
    );

    const setStatus = useCallback(
        (status: FrameworkStatus | null) => visit({ ...filters, status }),
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
        frameworks: initial,
        filters,
        isFiltered,
        setSearch,
        setStatus,
        clearFilters,
    };
}
