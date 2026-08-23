import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import RuleGraph from '../components/rule-graph';
import { useRuleSetScope } from '../hooks/use-rule-scope';
import type {
    Outcome,
    RuleGraph as RuleGraphData,
    RuleSet,
} from '../types/game-rules';

type RuleGraphPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSet: { data: RuleSet };
    graph: { data: RuleGraphData };
    victoryConditions: { data: Outcome[] };
    endConditions: { data: Outcome[] };
};

/**
 * The flow of a game, on its own.
 *
 * Read-only, deliberately — the phase designer edits phases and transitions, and this is what those *are*
 * when drawn. Splitting them is what keeps the diagram legible: a canvas that also had to support dragging,
 * connecting and deleting would spend its space on affordances rather than on the shape of the game.
 *
 * The outcomes are here because a flow that stops at "Cleanup" does not show how the game finishes, and
 * "what ends this?" is the question somebody opens this page to answer.
 */
export default function RuleGraphPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSet: { data: ruleSet },
    graph: { data: graph },
    victoryConditions: { data: victoryConditions },
    endConditions: { data: endConditions },
}: RuleGraphPageProps) {
    const { t } = useTranslation();
    const scope = useRuleSetScope(workspace, game, version, ruleSet);

    return (
        <>
            <Head
                title={t('Flow · :ruleSet · :game', {
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

                    <Button variant="outline" size="sm" asChild>
                        <Link href={rules.phases.url(scope)}>
                            <Pencil className="size-4" />
                            {t('Edit the phases')}
                        </Link>
                    </Button>
                </div>

                <div className="max-w-2xl">
                    <RuleGraph
                        graph={graph}
                        victoryConditions={victoryConditions}
                        endConditions={endConditions}
                    />
                </div>
            </div>
        </>
    );
}
