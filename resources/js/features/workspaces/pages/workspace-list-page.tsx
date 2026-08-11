import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import workspaces from '@/routes/workspaces';
import WorkspaceCard from '../components/workspace-card';
import type { Workspace } from '../types/workspace';

type WorkspaceListPageProps = {
    workspaces: { data: Workspace[] };
};

/**
 * The workspaces the signed in account belongs to.
 *
 * The list arrives already scoped to membership, so there is nothing to
 * filter here.
 */
export default function WorkspaceListPage({
    workspaces: { data },
}: WorkspaceListPageProps) {
    return (
        <>
            <Head title="Workspaces" />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Workspaces"
                        description="The spaces you design games in, alone or with a team"
                    />

                    <Button asChild data-test="create-workspace-button">
                        <Link href={workspaces.create()}>
                            <Plus className="size-4" />
                            New workspace
                        </Link>
                    </Button>
                </div>

                {data.length === 0 ? (
                    <div className="rounded-lg border border-dashed px-6 py-16 text-center">
                        <p className="font-medium">No workspaces yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create one to start a board-game project, then
                            invite the people you design with.
                        </p>

                        <Button asChild className="mt-6">
                            <Link href={workspaces.create()}>
                                <Plus className="size-4" />
                                Create your first workspace
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {data.map((workspace) => (
                            <WorkspaceCard
                                key={workspace.id}
                                workspace={workspace}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
