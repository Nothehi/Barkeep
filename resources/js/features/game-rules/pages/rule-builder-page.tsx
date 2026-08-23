import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import ActionList from '../components/action-list';
import RuleTree from '../components/rule-tree';
import { useRulePermissions } from '../hooks/use-permissions';
import { useRuleSetScope } from '../hooks/use-rule-scope';
import type {
    GamePhase,
    GameRule,
    RuleAction,
    RuleOptions,
    RuleReference,
    RuleSet,
} from '../types/game-rules';

type RuleBuilderPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSet: { data: RuleSet };
    rules: { data: GameRule[] };
    phases: { data: GamePhase[] };
    actions: { data: RuleAction[] };
    references: { data: RuleReference[] };
    options: RuleOptions;
};

/**
 * The structured editor for a rule system.
 *
 *     Rule set
 *     ├── Setup
 *     └── Round
 *         ├── Round start
 *         ├── Action phase
 *         └── Cleanup
 *
 * Section 44 of the module brief, and the constraint that matters in it: this is not an unstructured text
 * editor. A rulebook typed into a textarea is a document nothing can validate, nothing can clone reliably
 * and nothing can turn into a graph — which is the whole reason this module models rules as records rather
 * than as prose.
 *
 * Just the tree and the actions. The dashboard shows everything at once; this page is for the half hour
 * somebody spends writing rules, and the other six panels would be in the way.
 */
export default function RuleBuilderPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSet: { data: ruleSet },
    rules: { data: ruleList },
    phases: { data: phases },
    actions: { data: actions },
    options,
}: RuleBuilderPageProps) {
    const { t } = useTranslation();
    const scope = useRuleSetScope(workspace, game, version, ruleSet);
    const canEdit = useRulePermissions(ruleSet).canEdit;

    return (
        <>
            <Head
                title={t('Builder · :ruleSet · :game', {
                    ruleSet: ruleSet.name,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={rules.show.url(scope)}>
                            <ArrowLeft className="size-4 rtl:rotate-180" />
                            {t('Back to the rule set')}
                        </Link>
                    </Button>

                    <h1 className="text-lg font-semibold" dir="auto">
                        {ruleSet.name}
                    </h1>
                </div>

                <RuleTree
                    rules={ruleList}
                    phases={phases}
                    options={options}
                    scope={scope}
                    canEdit={canEdit}
                />

                <ActionList
                    actions={actions}
                    phases={phases}
                    options={options}
                    economy={{ available: false, actions: [], resources: [] }}
                    scope={scope}
                    canEdit={canEdit}
                />
            </div>
        </>
    );
}
