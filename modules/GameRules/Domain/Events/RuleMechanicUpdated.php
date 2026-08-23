<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a named mechanism is renamed or recategorised.
 *
 * Carries the names of the columns that actually changed rather than the record,
 * so a consumer can decide whether it cares without loading anything — and so
 * that a save which changed nothing dispatches nothing at all.
 */
final readonly class RuleMechanicUpdated
{
    public function __construct(
        public string $mechanicId,
        public string $ruleSetId,
        /** @var list<string> */
        public array $changedFields,
    ) {}
}
