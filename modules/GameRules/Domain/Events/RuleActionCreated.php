<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set declares something a player may do.

 * A `RuleAction`, which answers "what can the player do?". What it costs is
 * GameEconomy's `EconomyAction`, and the two are joined by a handle rather than
 * by a foreign key.
 */
final readonly class RuleActionCreated
{
    public function __construct(
        public string $actionId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
