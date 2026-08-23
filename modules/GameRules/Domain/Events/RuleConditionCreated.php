<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a reusable logical requirement is named.

 * Conditions are named so they can be pointed at from the several places that
 * care — a transition, an end condition, a trigger — which is why the name rather
 * than a derived handle travels with the event.
 */
final readonly class RuleConditionCreated
{
    public function __construct(
        public string $conditionId,
        public string $ruleSetId,
        public string $name,
    ) {}
}
