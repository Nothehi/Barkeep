import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import type { Game, GameVersion } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import BalanceProfileList from '../components/balance-profile-list';
import CreateBalanceProfileDialog from '../components/create-balance-profile-dialog';
import { useBalanceScope } from '../hooks/use-balance-scope';
import type { BalanceProfile } from '../types/game-economy';

type BalanceProfilesPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    profiles: { data: BalanceProfile[] };
    can: { create: boolean };
};

/**
 * The balance configurations of one design state.
 *
 * The version is in the heading rather than assumed, because it is the thing that makes this list mean
 * anything: these are the numbers as of v4, and v3's numbers are a different page that is still readable.
 */
export default function BalanceProfilesPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    profiles: { data: profiles },
    can,
}: BalanceProfilesPageProps) {
    const { t } = useTranslation();
    const scope = useBalanceScope(workspace, game, version);

    return (
        <>
            <Head
                title={t('Balance · :version · :game', {
                    version: version.label,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title={t('Balance')}
                        description={t(
                            'The numbers behind :version — what resources exist, what actions cost, and what is being tuned',
                            { version: version.label },
                        )}
                    />

                    {can.create && (
                        <CreateBalanceProfileDialog
                            scope={scope}
                            versionLabel={version.label}
                        />
                    )}
                </div>

                <BalanceProfileList profiles={profiles} scope={scope} />
            </div>
        </>
    );
}
