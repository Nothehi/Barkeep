<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule is removed from a set.

 * Its requirements and effects go with it. Its children do not: they are promoted
 * to the level above, because a designer deleting a heading usually means "these
 * are not a group any more" rather than "delete these four rules".
 */
final readonly class GameRuleDeleted
{
    public function __construct(
        public string $ruleId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
