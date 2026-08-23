<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set records something that ends the game.

 * Distinct from a victory condition, and the difference is the one designers most
 * often blur. "The deck runs out" stops the game; it does not say who won.
 */
final readonly class GameEndConditionCreated
{
    public function __construct(
        public string $outcomeId,
        public string $ruleSetId,
        public string $name,
        public bool $isMeasurable,
    ) {}
}
