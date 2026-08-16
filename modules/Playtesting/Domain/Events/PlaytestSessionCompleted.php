<?php

namespace Modules\Playtesting\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a session ends, with what it produced.
 *
 * The richest event in the module, and deliberately so: this is the moment
 * evidence exists, and it is the event the rest of the platform will be built
 * to react to. Everything a consumer would otherwise have to go and count is
 * already here, so that awarding experience for a playtest or rolling a figure
 * into a report never requires reading Playtesting's tables from outside.
 *
 * The duration is null for a session with no start — which cannot happen
 * through the module's own commands, since completing requires a running
 * session, but the type says so rather than assuming it.
 */
final readonly class PlaytestSessionCompleted
{
    public function __construct(
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public string $gameVersionId,
        public string $completedBy,
        public DateTimeImmutable $endedAt,
        public ?int $durationSeconds,
        public int $participantCount,
        public int $observationCount,
        public int $feedbackCount,
    ) {}
}
