import { Head } from '@inertiajs/react';
import type { Game, GameVersion } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import CreateRuleSetDialog from '../components/create-rule-set-dialog';
import RuleSetList from '../components/rule-set-list';
import { useRuleScope } from '../hooks/use-rule-scope';
import type { RuleSet } from '../types/game-rules';

type RuleSetsPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSets: { data: RuleSet[] };
    activeRuleSet: { data: RuleSet } | null;
    canCreate: boolean;
};

/**
 * The rule systems written for one design version.
 *
 * A short list — a draft, the one in play, and the archived ones before it — and that shortness is the whole
 * shape of the module's lifecycle: a studio does not accumulate rule sets, it clones one, changes it, and
 * activates it.
 */
export default function RuleSetsPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSets: { data: ruleSets },
    canCreate,
}: RuleSetsPageProps) {
    const { t } = useTranslation();
    const scope = useRuleScope(workspace, game, version);

    return (
        <>
            <Head
                title={t('Rules · :version · :game', {
                    version: version.label,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">{t('Rules')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t(
                                'The rules belong to :version, so a playtest run against it stays readable later.',
                                { version: version.label },
                            )}
                        </p>
                    </div>

                    {canCreate && <CreateRuleSetDialog scope={scope} />}
                </div>

                <RuleSetList ruleSets={ruleSets} scope={scope} />
            </div>
        </>
    );
}
