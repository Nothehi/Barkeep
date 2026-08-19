<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when the work on an iteration actually begins.
 *
 * A separate event from creation because the gap between the two is real and
 * often long: a cycle can sit planned for weeks while the current one finishes.
 * Anything measuring how long design work takes has to date it from here rather
 * than from when somebody wrote the plan down.
 */
final readonly class IterationStarted
{
    public function __construct(
        public string $iterationId,
        public string $gameId,
        public string $prototypeVersionId,
        public string $startedBy,
        public DateTimeImmutable $startedAt,
    ) {}
}
