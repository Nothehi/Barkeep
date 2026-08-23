import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import RuleAnalysis from '../components/rule-analysis';
import { useRuleSetScope } from '../hooks/use-rule-scope';
import type { RuleSet, RuleSetAnalysis } from '../types/game-rules';

type RuleAnalysisPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSet: { data: RuleSet };
    analysis: { data: RuleSetAnalysis };
};

/**
 * What the module makes of a rule system, on its own page.
 *
 * The same numbers the dashboard shows at the bottom, with room to read them. Somebody triaging a long list
 * of findings wants the whole screen for it rather than a panel under eight other panels.
 */
export default function RuleAnalysisPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSet: { data: ruleSet },
    analysis: { data: analysis },
}: RuleAnalysisPageProps) {
    const { t } = useTranslation();
    const scope = useRuleSetScope(workspace, game, version, ruleSet);

    return (
        <>
            <Head
                title={t('Analysis · :ruleSet · :game', {
                    ruleSet: ruleSet.name,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={rules.show.url(scope)}>
                        <ArrowLeft className="size-4 rtl:rotate-180" />
                        {t('Back to the rule set')}
                    </Link>
                </Button>

                <RuleAnalysis
                    summary={analysis.summary}
                    errors={analysis.errors}
                    warnings={analysis.warnings}
                    scope={scope}
                />
            </div>
        </>
    );
}
