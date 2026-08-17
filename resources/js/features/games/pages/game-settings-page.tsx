import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Workspace } from '@/features/workspaces';
import { archiveGame } from '../api';
import DesignPhasePicker from '../components/design-phase-picker';
import DesignRecordForm from '../components/design-record-form';
import GameHeader from '../components/game-header';
import { useGamePermissions } from '../hooks/use-game-permissions';
import { useUpdateGame } from '../hooks/use-update-game';
import type { ComplexityOptions, DesignRecord } from '../types/design-record';
import type { Game, GameOptions } from '../types/game';
import type { Mechanic } from '../types/mechanic';

type GameSettingsPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };

    /**
     * Null when the designer has decided nothing, which is most games. The form
     * renders empty from that rather than being handed a record full of nulls,
     * so "not decided" stays distinguishable from "decided to leave blank".
     */
    design_record: { data: DesignRecord } | null;

    /** The whole vocabulary, because the picker cannot be filled in client-side. */
    mechanics: { data: Mechanic[] };

    options: Pick<GameOptions, 'design_phases'> & ComplexityOptions;
};

/**
 * A game's settings and its danger zone.
 *
 * Archiving lives here rather than beside the lifecycle buttons on the game
 * header, and deliberately so: every other lifecycle move can be walked back,
 * and this one cannot. Putting it behind a confirmation on a settings screen
 * is the difference between an action somebody chose and one they hit while
 * reaching for "put on hold".
 */
export default function GameSettingsPage({
    workspace: { data: workspace },
    game: { data: game },
    design_record: designRecord,
    mechanics,
    options,
}: GameSettingsPageProps) {
    const permissions = useGamePermissions(game);
    const form = useUpdateGame(workspace.slug, game);

    const archive = () => {
        if (
            !window.confirm(
                `Archive ${game.name}? It becomes read-only for everyone in ${workspace.name}, and cannot be reopened. Nothing is deleted.`,
            )
        ) {
            return;
        }

        archiveGame(workspace.slug, game.slug);
    };

    return (
        <>
            <Head title={`Settings · ${game.name}`} />

            <div className="space-y-8 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <section className="max-w-xl space-y-6">
                    <Heading
                        variant="small"
                        title="General"
                        description="The game's name, address and description"
                    />

                    <form
                        className="space-y-6"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.submit();
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>

                            <Input
                                id="name"
                                name="name"
                                value={form.input.name}
                                onChange={(event) =>
                                    form.setName(event.target.value)
                                }
                                disabled={!permissions.canUpdate}
                                required
                            />

                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="slug">Address</Label>

                            <Input
                                id="slug"
                                name="slug"
                                value={form.input.slug}
                                onChange={(event) =>
                                    form.setSlug(event.target.value)
                                }
                                disabled={!permissions.canUpdate}
                                autoComplete="off"
                                spellCheck={false}
                                required
                            />

                            <p className="text-sm text-muted-foreground">
                                Changing this changes every link to the game. It
                                only has to be unique inside this workspace.
                            </p>

                            <InputError message={form.errors.slug} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>

                            <Textarea
                                id="description"
                                name="description"
                                value={form.input.description}
                                onChange={(event) =>
                                    form.setDescription(event.target.value)
                                }
                                disabled={!permissions.canUpdate}
                                rows={3}
                            />

                            <InputError message={form.errors.description} />
                        </div>

                        {permissions.canUpdate && (
                            <Button
                                type="submit"
                                disabled={form.processing || !form.isValid}
                                data-test="save-game-button"
                            >
                                {form.processing && <Spinner />}
                                Save
                            </Button>
                        )}
                    </form>
                </section>

                <Separator />

                <section className="max-w-xl space-y-4">
                    <Heading
                        variant="small"
                        title="Design phase"
                        description="Where the design currently is, which is a separate question from whether anybody is working on it"
                    />

                    <DesignPhasePicker
                        game={game}
                        workspace={workspace.slug}
                        options={options.design_phases}
                    />
                </section>

                <Separator />

                {/*
                 * Wider than the sections above it, because the mechanics picker
                 * is a wrapping set of chips and a vocabulary of forty terms in a
                 * single column is a scroll rather than a choice.
                 */}
                <section className="max-w-3xl space-y-6">
                    <Heading
                        variant="small"
                        title="Design"
                        description="What has been decided about the game itself. A framework reads these to answer its own factual questions, so an empty field means 'not yet' rather than 'no'."
                    />

                    <DesignRecordForm
                        workspace={workspace.slug}
                        game={game}
                        record={designRecord?.data ?? null}
                        mechanics={mechanics.data}
                        options={options}
                        canEdit={permissions.canUpdateDesignRecord}
                    />
                </section>

                {permissions.canArchive && (
                    <>
                        <Separator />

                        <section className="max-w-xl space-y-4">
                            <Heading
                                variant="small"
                                title="Danger zone"
                                description="Changes here affect everyone in the workspace"
                            />

                            <div className="space-y-4 rounded-lg border border-destructive/40 p-4">
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium">
                                            Archive game
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Make it read-only. Nothing is
                                            deleted, but it cannot be reopened.
                                        </p>
                                    </div>

                                    <Button
                                        variant="destructive"
                                        onClick={archive}
                                        data-test="archive-game-button"
                                    >
                                        Archive game
                                    </Button>
                                </div>
                            </div>
                        </section>
                    </>
                )}
            </div>
        </>
    );
}
