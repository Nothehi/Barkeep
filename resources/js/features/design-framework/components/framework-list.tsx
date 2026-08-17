import { Library } from 'lucide-react';
import type { Framework } from '../types/framework';
import FrameworkCard from './framework-card';

type FrameworkListProps = {
    frameworks: Framework[];
    isFiltered: boolean;
};

/**
 * The platform's methodologies.
 *
 * The empty state distinguishes "nothing matched" from "nothing exists",
 * because they call for opposite reactions: one wants the filters cleared and
 * the other wants a framework written.
 */
export default function FrameworkList({
    frameworks,
    isFiltered,
}: FrameworkListProps) {
    if (frameworks.length === 0) {
        return (
            <div
                className="flex flex-col items-center gap-2 rounded-lg border border-dashed px-6 py-12 text-center"
                data-test="framework-list-empty"
            >
                <Library className="size-6 text-muted-foreground" />

                <p className="text-sm font-medium">
                    {isFiltered
                        ? 'No frameworks match those filters'
                        : 'No design frameworks yet'}
                </p>

                <p className="max-w-md text-sm text-muted-foreground">
                    {isFiltered
                        ? 'Try a different search, or clear the status filter.'
                        : 'A framework is a methodology a game can choose to follow — its phases, the questions it asks, and the practices it recommends.'}
                </p>
            </div>
        );
    }

    return (
        <div
            className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            data-test="framework-list"
        >
            {frameworks.map((framework) => (
                <FrameworkCard key={framework.id} framework={framework} />
            ))}
        </div>
    );
}
