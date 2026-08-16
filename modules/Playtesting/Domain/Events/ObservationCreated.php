<?php

namespace Modules\Playtesting\Domain\Events;

use Modules\Playtesting\Domain\Enums\ObservationCategory;

/**
 * Dispatched when a designer records something they noticed.
 *
 * The category travels with it because it is the one part of an observation
 * that is machine-readable. Everything else is prose written at a table, and a
 * consumer that tried to interpret it would be guessing.
 */
final readonly class ObservationCreated
{
    public function __construct(
        public string $observationId,
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public ObservationCategory $category,
        public string $createdBy,
    ) {}
}
