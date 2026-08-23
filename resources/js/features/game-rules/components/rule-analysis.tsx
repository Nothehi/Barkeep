import { RefreshCw, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { analyseRuleSet, validateRuleSet } from '../api';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import type { RuleSetSummary, ValidationError } from '../types/game-rules';
import RuleValidation from './rule-validation';
import SummaryTiles from './summary-tiles';

type RuleAnalysisProps = {
    summary: RuleSetSummary;
    errors: ValidationError[];
    warnings: ValidationError[];
    scope: RuleSetScope;
};

/**
 * What the module makes of a rule system: how much of it there is, and what holds together.
 *
 * The two buttons and the panel below them read exactly the same numbers. The difference is that pressing a
 * button *announces* it — the studio's event stream records that somebody checked, and the page rendering
 * does not, because a refresh is not a decision.
 *
 * Static, in the strong sense. Nothing here was executed, simulated or played: the module counts records,
 * walks the phase graph and applies a fixed list of checks. That is the whole of it, and it is why the same
 * rule set always produces the same list.
 */
export default function RuleAnalysis({
    summary,
    errors,
    warnings,
    scope,
}: RuleAnalysisProps) {
    const { t } = useTranslation();
    const [working, setWorking] = useState(false);

    return (
        <section className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-base font-semibold">{t('Analysis')}</h2>

                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={working}
                        onClick={() => {
                            setWorking(true);
                            validateRuleSet(scope, {
                                onFinish: () => setWorking(false),
                            });
                        }}
                        data-test="validate-rule-set"
                    >
                        <ShieldCheck className="size-4" />
                        {t('Check the rules')}
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        disabled={working}
                        onClick={() => {
                            setWorking(true);
                            analyseRuleSet(scope, {
                                onFinish: () => setWorking(false),
                            });
                        }}
                        data-test="analyse-rule-set"
                    >
                        <RefreshCw className="size-4" />
                        {t('Analyse')}
                    </Button>
                </div>
            </div>

            <SummaryTiles summary={summary} />

            <RuleValidation errors={errors} warnings={warnings} />
        </section>
    );
}
