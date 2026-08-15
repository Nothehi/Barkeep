import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { archiveWorkspace, leaveWorkspace, updateWorkspace } from '../api';
import TransferOwnershipDialog from '../components/transfer-ownership-dialog';
import WorkspaceHeader from '../components/workspace-header';
import { useWorkspacePermissions } from '../hooks/use-workspace-permissions';
import type { Workspace, WorkspaceMember } from '../types/workspace';

type WorkspaceSettingsPageProps = {
    workspace: { data: Workspace };
    members: { data: WorkspaceMember[] };
};

/**
 * A workspace's general settings and its danger zone.
 *
 * The danger zone offers only what the caller can actually do: archival is
 * the owner's alone, and leaving is offered to everyone except the owner,
 * who has to transfer the workspace first.
 */
export default function WorkspaceSettingsPage({
    workspace: { data: workspace },
    members,
}: WorkspaceSettingsPageProps) {
    const permissions = useWorkspacePermissions(workspace);

    const [name, setName] = useState(workspace.name);
    const [slug, setSlug] = useState(workspace.slug);
    const [description, setDescription] = useState(workspace.description ?? '');
    const [errors, setErrors] = useState<Record<string, string | undefined>>(
        {},
    );
    const [processing, setProcessing] = useState(false);

    const save = () => {
        setProcessing(true);
        setErrors({});

        updateWorkspace(
            workspace.slug,
            { name, slug, description },
            {
                onError: setErrors,
                onFinish: () => setProcessing(false),
            },
        );
    };

    const archive = () => {
        if (
            !window.confirm(
                `Archive ${workspace.name}? It becomes read-only for everyone. Nothing is deleted.`,
            )
        ) {
            return;
        }

        archiveWorkspace(workspace.slug);
    };

    const leave = () => {
        if (
            !window.confirm(
                `Leave ${workspace.name}? You will lose access until somebody invites you back.`,
            )
        ) {
            return;
        }

        leaveWorkspace(workspace.slug);
    };

    return (
        <>
            <Head title={`Settings · ${workspace.name}`} />

            <div className="space-y-8 px-4 py-6">
                <WorkspaceHeader workspace={workspace} />

                <section className="max-w-xl space-y-6">
                    <Heading
                        variant="small"
                        title="General"
                        description="The workspace's name, address and description"
                    />

                    <form
                        className="space-y-6"
                        onSubmit={(event) => {
                            event.preventDefault();
                            save();
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>

                            <Input
                                id="name"
                                name="name"
                                value={name}
                                onChange={(event) =>
                                    setName(event.target.value)
                                }
                                disabled={!permissions.canUpdate}
                                required
                            />

                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="slug">Address</Label>

                            <Input
                                id="slug"
                                name="slug"
                                value={slug}
                                onChange={(event) =>
                                    setSlug(event.target.value)
                                }
                                disabled={!permissions.canUpdate}
                                autoComplete="off"
                                spellCheck={false}
                                required
                            />

                            <p className="text-sm text-muted-foreground">
                                Changing this changes every link to the
                                workspace.
                            </p>

                            <InputError message={errors.slug} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>

                            <Textarea
                                id="description"
                                name="description"
                                value={description}
                                onChange={(event) =>
                                    setDescription(event.target.value)
                                }
                                disabled={!permissions.canUpdate}
                                rows={3}
                            />

                            <InputError message={errors.description} />
                        </div>

                        {permissions.canUpdate && (
                            <Button
                                type="submit"
                                disabled={processing}
                                data-test="save-workspace-button"
                            >
                                {processing && <Spinner />}
                                Save
                            </Button>
                        )}
                    </form>
                </section>

                <Separator />

                <section className="max-w-xl space-y-4">
                    <Heading
                        variant="small"
                        title="Danger zone"
                        description="Changes here affect everyone in the workspace"
                    />

                    <div className="space-y-4 rounded-lg border border-destructive/40 p-4">
                        {permissions.canTransferOwnership && (
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium">
                                        Transfer ownership
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Hand the workspace to another member.
                                    </p>
                                </div>

                                <TransferOwnershipDialog
                                    workspace={workspace}
                                    members={members.data}
                                />
                            </div>
                        )}

                        {permissions.canArchive && (
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium">
                                        Archive workspace
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Make it read-only. Nothing is deleted.
                                    </p>
                                </div>

                                <Button
                                    variant="destructive"
                                    onClick={archive}
                                    data-test="archive-workspace-button"
                                >
                                    Archive workspace
                                </Button>
                            </div>
                        )}

                        {permissions.canLeave && (
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium">
                                        Leave workspace
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Give up your own access to this
                                        workspace.
                                    </p>
                                </div>

                                <Button
                                    variant="destructive"
                                    onClick={leave}
                                    data-test="leave-workspace-button"
                                >
                                    Leave workspace
                                </Button>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}
