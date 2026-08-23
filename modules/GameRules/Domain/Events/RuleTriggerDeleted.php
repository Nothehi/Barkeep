<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a trigger is removed.
 */
final readonly class RuleTriggerDeleted
{
    public function __construct(
        public string $triggerId,
        public string $ruleSetId,
        public string $name,
    ) {}
}
