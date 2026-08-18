import { Head, Link, router } from '@inertiajs/react';
import { Archive, Plus, Users } from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import workspaces from '@/routes/workspaces';
import type { Workspace } from '../types/workspace';

type SelectWorkspacePageProps = {
    workspaces: { data: Workspace[] };
};

/**
 * The step between signing in and the app.
 *
 * Almost every screen past here is about one workspace, so this asks which
 * before letting anybody in rather than guessing and being wrong. It stands
 * outside the signed-in shell on purpose: the shell's own switcher would be
 * offering the choice the page is already asking for.
 *
 * The list is the one the server shares with every page, already scoped to
 * membership. Choosing is a request like any other — the server authorizes the
 * workspace against the policy before it remembers anything.
 */
export default function SelectWorkspacePage({
    workspaces: { data },
}: SelectWorkspacePageProps) {
    const { t, choice } = useTranslation();
    const [selecting, setSelecting] = useState<string | null>(null);

    const choose = (workspace: Workspace) => {
        setSelecting(workspace.slug);

        router.post(
            workspaces.activate.url(workspace.slug),
            {},
            { onFinish: () => setSelecting(null) },
        );
    };

    return (
        <>
            <Head title={t('Choose a workspace')} />

            <div className="mx-auto flex min-h-svh max-w-md flex-col justify-center gap-8 px-4 py-12">
                <div className="flex flex-col items-center gap-4">
                    <AppLogoIcon className="size-9 fill-current text-[var(--foreground)] dark:text-white" />

                    <div className="space-y-2 text-center">
                        <h1 className="text-xl font-medium">
                            {t('Choose a workspace')}
                        </h1>

                        <p className="text-sm text-muted-foreground">
                            {t(
                                'Your games, playtests and framework progress all live in a workspace. Pick the one you want to work in.',
                            )}
                        </p>
                    </div>
                </div>

                <ul className="space-y-2">
                    {data.map((workspace) => (
                        <li key={workspace.id}>
                            <button
                                type="button"
                                onClick={() => choose(workspace)}
                                disabled={selecting !== null}
                                data-test={`select-workspace-${workspace.slug}`}
                                className="flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-start transition-colors hover:border-ring disabled:opacity-60"
                            >
                                <span className="min-w-0 flex-1">
                                    <span className="flex items-center gap-2">
                                        <span
                                            className="truncate font-medium"
                                            dir="auto"
                                        >
                                            {workspace.name}
                                        </span>

                                        {workspace.status === 'archived' && (
                                            <Badge
                                                variant="secondary"
                                                className="shrink-0"
                                            >
                                                <Archive />
                                                {t('Archived')}
                                            </Badge>
                                        )}
                                    </span>

                                    {workspace.members_count !== undefined && (
                                        <span className="mt-0.5 flex items-center gap-1.5 text-sm text-muted-foreground">
                                            <Users className="size-4" />
                                            {choice(
                                                ':count member|:count members',
                                                workspace.members_count,
                                            )}
                                        </span>
                                    )}
                                </span>

                                {selecting === workspace.slug && <Spinner />}
                            </button>
                        </li>
                    ))}
                </ul>

                <Button variant="outline" asChild>
                    <Link href={workspaces.create()}>
                        <Plus className="size-4" />
                        {t('Create a new workspace')}
                    </Link>
                </Button>
            </div>
        </>
    );
}
