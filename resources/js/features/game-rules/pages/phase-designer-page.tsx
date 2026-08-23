import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import PhaseList from '../components/phase-list';
import PhaseTransitionEditor from '../components/phase-transition-editor';
import RuleGraph from '../components/rule-graph';
import { useRulePermissions } from '../hooks/use-permissions';
import { useRuleSetScope } from '../hooks/use-rule-scope';
import type {
    GamePhase,
    PhaseTransition,
    RuleCondition,
    RuleGraph as RuleGraphData,
    RuleOptions,
    RuleSet,
    RuleTrigger,
} from '../types/game-rules';

type PhaseDesignerPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSet: { data: RuleSet };
    phases: { data: GamePhase[] };
    transitions: { data: PhaseTransition[] };
    conditions: { data: RuleCondition[] };
    triggers: { data: RuleTrigger[] };
    graph: { data: RuleGraphData };
    options: RuleOptions;
};

/**
 * The turn structure, edited beside the flow it produces.
 *
 * Phases and transitions on the left, the diagram on the right. The pairing is the point: a designer sees
 * the consequence of an edge as soon as they draw it, and the phase that was unreachable a moment ago stops
 * being flagged without them having to go and look.
 *
 * No graph library. Section 45 of the brief says to avoid adding one unless the project already has it, and
 * it does not — so the diagram is a vertical run of boxes in React and Tailwind, which is what a board game's
 * turn structure mostly looks like anyway.
 */
export default function PhaseDesignerPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSet: { data: ruleSet },
    phases: { data: phases },
    transitions: { data: transitions },
    conditions: { data: conditions },
    triggers: { data: triggers },
    graph: { data: graph },
    options,
}: PhaseDesignerPageProps) {
    const { t } = useTranslation();
    const scope = useRuleSetScope(workspace, game, version, ruleSet);
    const canEdit = useRulePermissions(ruleSet).canEdit;

    return (
        <>
            <Head
                title={t('Phases · :ruleSet · :game', {
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

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="space-y-6">
                        <PhaseList
                            phases={phases}
                            options={options}
                            scope={scope}
                            canEdit={canEdit}
                        />

                        <PhaseTransitionEditor
                            transitions={transitions}
                            phases={phases}
                            conditions={conditions}
                            triggers={triggers}
                            scope={scope}
                            canEdit={canEdit}
                        />
                    </div>

                    <RuleGraph graph={graph} />
                </div>
            </div>
        </>
    );
}
