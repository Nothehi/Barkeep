<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set records a way to be knocked out.

 * Separate from a victory condition because losing is not the negation of
 * winning: most games with defeat conditions also have somebody who wins.
 */
final readonly class DefeatConditionCreated
{
    public function __construct(
        public string $outcomeId,
        public string $ruleSetId,
        public string $name,
        public bool $isMeasurable,
    ) {}
}
