<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when an action is renamed, moved to another phase, or rewired to the
 * economy.
 *
 * Carries the names of the columns that actually changed rather than the record,
 * so a consumer can decide whether it cares without loading anything — and so
 * that a save which changed nothing dispatches nothing at all.
 */
final readonly class RuleActionUpdated
{
    public function __construct(
        public string $actionId,
        public string $ruleSetId,
        /** @var list<string> */
        public array $changedFields,
    ) {}
}
