<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a transition's guard, destination or order changes.
 *
 * Carries the names of the columns that actually changed rather than the record,
 * so a consumer can decide whether it cares without loading anything — and so
 * that a save which changed nothing dispatches nothing at all.
 */
final readonly class PhaseTransitionUpdated
{
    public function __construct(
        public string $transitionId,
        public string $ruleSetId,
        /** @var list<string> */
        public array $changedFields,
    ) {}
}
