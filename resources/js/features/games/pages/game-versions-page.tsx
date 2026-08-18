import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import CreateVersionDialog from '../components/create-version-dialog';
import GameHeader from '../components/game-header';
import GameVersionList from '../components/game-version-list';
import type { Game, GameVersion } from '../types/game';

type GameVersionsPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    versions: { data: GameVersion[] };
};

/**
 * A game's iterations.
 *
 * Newest first, with the current one marked. There is no comparison between
 * versions: a version records that an iteration existed and what changed in
 * prose, and diffing two of them needs design documents that do not exist
 * yet.
 */
export default function GameVersionsPage({
    workspace: { data: workspace },
    game: { data: game },
    versions: { data: versions },
}: GameVersionsPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Versions · :game', { game: game.name })} />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title={t('Versions')}
                        description={t(
                            'Every iteration this game has been through',
                        )}
                    />

                    <CreateVersionDialog
                        game={game}
                        workspace={workspace.slug}
                        versions={versions}
                    />
                </div>

                <GameVersionList
                    versions={versions}
                    workspace={workspace.slug}
                    game={game.slug}
                />
            </div>
        </>
    );
}
