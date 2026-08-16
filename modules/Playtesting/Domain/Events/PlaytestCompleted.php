<?php

namespace Modules\Playtesting\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a designer closes an investigation.
 *
 * The session count travels with it because "completed after one session" and
 * "completed after eleven" are different events to anything scoring effort or
 * confidence, and making a consumer go and count them would mean every
 * consumer reaching into Playtesting's tables to do it.
 */
final readonly class PlaytestCompleted
{
    public function __construct(
        public string $playtestId,
        public string $gameId,
        public string $gameVersionId,
        public string $completedBy,
        public DateTimeImmutable $completedAt,
        public int $sessionCount,
    ) {}
}
