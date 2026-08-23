<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set names something that happens automatically.

 * Recorded, never fired. Nothing in this module executes a trigger — see
 * `RuleTrigger` and section 23 of the module brief.
 */
final readonly class RuleTriggerCreated
{
    public function __construct(
        public string $triggerId,
        public string $ruleSetId,
        public string $name,
    ) {}
}
