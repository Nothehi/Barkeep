<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when an action is removed, along with its requirements and effects.
 */
final readonly class RuleActionDeleted
{
    public function __construct(
        public string $actionId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
