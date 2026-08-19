import { Head } from '@inertiajs/react';
import type { Game } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import PrototypeHeader from '../components/prototype-header';
import PrototypeVersionList from '../components/prototype-version-list';
import { usePrototypePermissions } from '../hooks/use-permissions';
import type { Prototype, PrototypeVersion } from '../types/prototype-iteration';

type PrototypePageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    prototype: { data: Prototype };
    versions: { data: PrototypeVersion[] };
};

/**
 * One prototype and every state it has been in.
 *
 * The version list is the whole page, because a prototype is not much more than the sequence of things it has
 * been. What the header adds is the pair of facts that place it: which design version it was built from, and
 * how many times it has been rebuilt since.
 */
export default function PrototypePage({
    workspace: { data: workspace },
    game: { data: game },
    prototype: { data: prototype },
    versions: { data: versions },
}: PrototypePageProps) {
    const { t } = useTranslation();
    const permissions = usePrototypePermissions(prototype);

    return (
        <>
            <Head
                title={t(':prototype · :game', {
                    prototype: prototype.name,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <PrototypeHeader
                    prototype={prototype}
                    workspace={workspace.slug}
                    game={game.slug}
                />

                <PrototypeVersionList
                    versions={versions}
                    workspace={workspace.slug}
                    game={game.slug}
                    prototype={prototype.id}
                    canCreateVersion={permissions.canCreateVersion}
                />
            </div>
        </>
    );
}
