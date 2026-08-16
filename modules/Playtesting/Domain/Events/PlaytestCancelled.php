<?php

namespace Modules\Playtesting\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a playtest is called off.
 *
 * Distinct from completion rather than a variant of it. A cancelled playtest
 * produced no answer, and a consumer that treated the two alike would credit a
 * designer for an investigation that never happened.
 */
final readonly class PlaytestCancelled
{
    public function __construct(
        public string $playtestId,
        public string $gameId,
        public string $gameVersionId,
        public string $cancelledBy,
        public DateTimeImmutable $cancelledAt,
    ) {}
}
