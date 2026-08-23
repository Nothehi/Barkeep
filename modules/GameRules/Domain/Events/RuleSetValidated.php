<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when somebody deliberately asks for the rule set to be checked.
 *
 * Pressing "Validate" is a fact about how a studio works, and it is worth
 * recording separately from the same numbers being computed to draw a screen.
 * The dashboard's own reading deliberately does not announce itself — a page
 * refresh is not a decision.
 *
 * Nothing is persisted by the run, here or anywhere: a validation result is a
 * reading of the rule set as it stands, and storing one would immediately create
 * a second question the module would have to keep answering.
 */
final readonly class RuleSetValidated
{
    public function __construct(
        public string $ruleSetId,
        public int $errorCount,
        public int $warningCount,
        public string $validatedBy,
    ) {}
}
