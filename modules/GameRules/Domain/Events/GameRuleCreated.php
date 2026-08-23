<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a designer writes a rule down.

 * Fires for the rule alone, before it has requirements, effects or children. That
 * is what a rule is when it is created — "we need something about line of
 * sight" comes before anybody has worked out what it says — so a consumer must
 * not read this as a complete description.
 */
final readonly class GameRuleCreated
{
    public function __construct(
        public string $ruleId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
