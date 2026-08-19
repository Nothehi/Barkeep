<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a design cycle is called off.
 *
 * Carries no outcome, and the absence is the information. A cancelled iteration
 * did not fail — failing is a result — it stopped, and anything that treated
 * this as a bad outcome would be scoring studios for changing their plans, which
 * is most of what designing is.
 */
final readonly class IterationCancelled
{
    public function __construct(
        public string $iterationId,
        public string $gameId,
        public string $cancelledBy,
        public DateTimeImmutable $cancelledAt,
    ) {}
}
