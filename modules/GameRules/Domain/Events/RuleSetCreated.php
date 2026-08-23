<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a designer starts writing a game's rules down.
 *
 * The rule set is empty at this point — no rules, no phases, no actions. What it
 * carries is the design state it describes, which is the fact every consumer
 * will want: a rule set belongs to a `GameVersion` and never to a game.
 */
final readonly class RuleSetCreated
{
    public function __construct(
        public string $ruleSetId,
        public string $gameVersionId,
        public string $createdBy,
    ) {}
}
