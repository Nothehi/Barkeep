<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a condition is removed.

 * Whatever pointed at it keeps its own row with the reference cleared, and the
 * validator reports the gap. A victory condition is not deleted because the
 * sentence that measured it was.
 */
final readonly class RuleConditionDeleted
{
    public function __construct(
        public string $conditionId,
        public string $ruleSetId,
        public string $name,
    ) {}
}
