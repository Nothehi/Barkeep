<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when an effect is removed.
 */
final readonly class RuleEffectDeleted
{
    public function __construct(
        public string $effectId,
        public string $ruleSetId,
        public string $target,
    ) {}
}
