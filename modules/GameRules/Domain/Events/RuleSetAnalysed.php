<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when somebody deliberately asks for the rule set to be analysed.
 *
 * Static analysis, in the sense section 31 of the brief means: counting what is
 * there, walking the phase graph and running the validator. Nothing is executed
 * and no game is simulated.
 *
 * Like {@see RuleSetValidated}, this fires for the deliberate act rather than for
 * the dashboard's own quiet reading of the same numbers.
 */
final readonly class RuleSetAnalysed
{
    public function __construct(
        public string $ruleSetId,
        public int $ruleCount,
        public int $phaseCount,
        public int $actionCount,
        public int $errorCount,
        public int $warningCount,
        public string $analysedBy,
    ) {}
}
