<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a phase is renamed, retyped, reordered or renested.
 *
 * Carries the names of the columns that actually changed rather than the record,
 * so a consumer can decide whether it cares without loading anything — and so
 * that a save which changed nothing dispatches nothing at all.
 */
final readonly class GamePhaseUpdated
{
    public function __construct(
        public string $phaseId,
        public string $ruleSetId,
        /** @var list<string> */
        public array $changedFields,
    ) {}
}
