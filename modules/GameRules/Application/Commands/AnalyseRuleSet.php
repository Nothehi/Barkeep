<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\RuleSetAnalysis;
use Modules\GameRules\Application\Queries\GetRuleSetAnalysis;
use Modules\GameRules\Domain\Events\RuleSetAnalysed;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;

/**
 * Analyse a rule set, on purpose.
 *
 * Static analysis in the sense section 31 of the brief means: count what is
 * there, walk the phase graph, run the validator. Nothing is executed and no game
 * is simulated — the module has no way to do either, and that is the point.
 *
 * A command rather than a query for the same reason {@see ValidateRuleSet} is: it
 * announces that somebody looked. The numbers themselves come from
 * {@see GetRuleSetAnalysis}, which the dashboard calls silently on every render,
 * so pressing the button and refreshing the page produce identical results and
 * only one of them is a decision.
 */
final class AnalyseRuleSet
{
    public function __construct(private readonly GetRuleSetAnalysis $analysis) {}

    public function handle(User $actor, RuleSet $ruleSet): RuleSetAnalysis
    {
        $analysis = $this->analysis->handle($ruleSet);

        event(new RuleSetAnalysed(
            ruleSetId: $ruleSet->getKey(),
            ruleCount: $analysis->summary->rules,
            phaseCount: $analysis->summary->phases,
            actionCount: $analysis->summary->actions,
            errorCount: $analysis->summary->errors,
            warningCount: $analysis->summary->warnings,
            analysedBy: $actor->getKey(),
        ));

        return $analysis;
    }
}
