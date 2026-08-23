<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule's own wording, type, phase or place in the tree changes.
 *
 * Carries the names of the columns that actually changed rather than the record,
 * so a consumer can decide whether it cares without loading anything — and so
 * that a save which changed nothing dispatches nothing at all.
 */
final readonly class GameRuleUpdated
{
    public function __construct(
        public string $ruleId,
        public string $ruleSetId,
        /** @var list<string> */
        public array $changedFields,
    ) {}
}
