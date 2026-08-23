<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a relationship between two rules is withdrawn.
 *
 * Both rules survive. Only the claim that they are connected goes away.
 */
final readonly class RuleReferenceDeleted
{
    public function __construct(
        public string $referenceId,
        public string $ruleId,
        public string $referencedRuleId,
    ) {}
}
