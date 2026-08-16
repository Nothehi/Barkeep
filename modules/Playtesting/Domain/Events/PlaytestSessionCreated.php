<?php

namespace Modules\Playtesting\Domain\Events;

/**
 * Dispatched when a sitting is scheduled against a playtest.
 *
 * Scheduling is not testing: nothing has been learned yet, and a consumer
 * looking for evidence wants {@see PlaytestSessionCompleted} instead. This is
 * for the things that care that something is coming — reminders, calendars,
 * the designer's own planning view.
 */
final readonly class PlaytestSessionCreated
{
    public function __construct(
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public string $createdBy,
    ) {}
}
