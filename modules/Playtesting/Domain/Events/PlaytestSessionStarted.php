<?php

namespace Modules\Playtesting\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when people sit down and the session actually begins.
 *
 * The timestamp is the one that was written to the session, not the moment
 * the listener runs, so anything deriving a duration from a pair of events
 * arrives at the same number the module itself reports.
 */
final readonly class PlaytestSessionStarted
{
    public function __construct(
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public string $startedBy,
        public DateTimeImmutable $startedAt,
    ) {}
}
