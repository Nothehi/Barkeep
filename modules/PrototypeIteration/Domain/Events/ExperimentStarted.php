<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when an experiment begins running.
 */
final readonly class ExperimentStarted
{
    public function __construct(
        public string $experimentId,
        public string $iterationId,
        public string $gameId,
        public string $startedBy,
        public DateTimeImmutable $startedAt,
    ) {}
}
