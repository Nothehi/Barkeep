<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a condition's subject, operator or value changes.

 * Worth a consumer's attention out of proportion to its size: everything pointing
 * at the condition now means something slightly different.
 *
 * Carries the names of the columns that actually changed rather than the record,
 * so a consumer can decide whether it cares without loading anything — and so
 * that a save which changed nothing dispatches nothing at all.
 */
final readonly class RuleConditionUpdated
{
    public function __construct(
        public string $conditionId,
        public string $ruleSetId,
        /** @var list<string> */
        public array $changedFields,
    ) {}
}
