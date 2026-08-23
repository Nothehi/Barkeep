<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a designer records what a rule or action does.
 */
final readonly class RuleEffectCreated
{
    public function __construct(
        public string $effectId,
        public string $ruleSetId,
        public string $target,
    ) {}
}
