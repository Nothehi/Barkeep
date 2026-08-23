<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set records a way to win.

 * `isMeasurable` says whether a condition was attached. Most victory conditions
 * are written before they are defined — "whoever has the most points" goes in on
 * day one — so a consumer should expect false as often as true.
 */
final readonly class VictoryConditionCreated
{
    public function __construct(
        public string $outcomeId,
        public string $ruleSetId,
        public string $name,
        public bool $isMeasurable,
    ) {}
}
