<?php

namespace Modules\DesignFramework\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a game ticks off one framework requirement.
 *
 * Carries the checklist as well as the item, because the interesting fact is
 * usually about the list — "prototype readiness is now complete" — and making
 * every consumer look up the parent to find out would mean every consumer
 * reading this module's tables.
 *
 * Like {@see PracticeCompleted}, it fires only on completion. Unticking is a
 * correction, not an event.
 */
final readonly class ChecklistItemCompleted
{
    public function __construct(
        public string $completionId,
        public string $gameFrameworkId,
        public string $gameId,
        public string $checklistId,
        public string $checklistItemId,
        public bool $completesChecklist,
        public string $completedBy,
        public DateTimeImmutable $completedAt,
    ) {}
}
