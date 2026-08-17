<?php

namespace Modules\DesignFramework\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a game finishes one of its framework's activities.
 *
 * Only fires when a practice becomes complete, not when it is un-ticked. A
 * consumer awarding something for work done should not have to reason about
 * whether the work was later disclaimed, and a "practice un-completed" event
 * would exist mainly to be mishandled.
 */
final readonly class PracticeCompleted
{
    public function __construct(
        public string $completionId,
        public string $gameFrameworkId,
        public string $gameId,
        public string $practiceId,
        public string $completedBy,
        public DateTimeImmutable $completedAt,
    ) {}
}
