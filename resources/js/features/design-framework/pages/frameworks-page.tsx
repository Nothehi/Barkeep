import { Head } from '@inertiajs/react';
import { Info, Search } from 'lucide-react';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import CreateFrameworkDialog from '../components/create-framework-dialog';
import FrameworkList from '../components/framework-list';
import { useFrameworks } from '../hooks/use-frameworks';
import type {
    Framework,
    FrameworkFilters,
    FrameworkOptions,
} from '../types/framework';

type FrameworksPageProps = {
    frameworks: { data: Framework[] };
    filters: FrameworkFilters;
    options: FrameworkOptions;
    can: { create: boolean };
    administration_configured: boolean;
};

/**
 * The platform's design methodologies.
 *
 * These live at `/app/frameworks` rather than under a workspace, which is the
 * interface telling the truth about the domain: a methodology is not a
 * studio's document. Everybody reads the same catalogue; only the people
 * configured to administer frameworks can write one.
 *
 * The "any status" sentinel exists because a Radix select cannot hold an empty
 * string as a value; it is translated back to null before it reaches the URL.
 */
const ANY_STATUS = 'any-status';

export default function FrameworksPage({
    frameworks: { data },
    filters: initialFilters,
    options,
    can,
    administration_configured: administrationConfigured,
}: FrameworksPageProps) {
    const { frameworks, filters, isFiltered, setSearch, setStatus } =
        useFrameworks(data, initialFilters);

    return (
        <>
            <Head title="Design frameworks" />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Design frameworks"
                        description="The methodologies a game can choose to follow"
                    />

                    {can.create && <CreateFrameworkDialog />}
                </div>

                {/*
                 * An installation with nobody configured to administer
                 * frameworks shows a read-only catalogue, and says so. That is
                 * far more useful to whoever is setting Barkeep up than a
                 * missing button they cannot account for.
                 */}
                {!administrationConfigured && (
                    <Alert data-test="administration-not-configured">
                        <Info className="size-4" />
                        <AlertTitle>Frameworks are read-only here</AlertTitle>
                        <AlertDescription>
                            No accounts are configured to administer design
                            frameworks, so nobody can write one yet. Set
                            <code className="mx-1 rounded bg-muted px-1 py-0.5 text-xs">
                                design-framework.administrators
                            </code>
                            to change that.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative min-w-56 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                        <Input
                            value={filters.search ?? ''}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search frameworks"
                            className="pl-9"
                            aria-label="Search frameworks"
                            data-test="framework-search"
                        />
                    </div>

                    <Select
                        value={filters.status ?? ANY_STATUS}
                        onValueChange={(value) =>
                            setStatus(
                                value === ANY_STATUS
                                    ? null
                                    : (value as FrameworkFilters['status']),
                            )
                        }
                    >
                        <SelectTrigger
                            className="w-44"
                            aria-label="Filter by status"
                            data-test="framework-status-filter"
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

                <FrameworkList
                    frameworks={frameworks}
                    isFiltered={isFiltered}
                />
            </div>
        </>
    );
}
