<?php

namespace Modules\Playtesting\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a session is called off, before or during.
 *
 * Whether anybody had already sat down is readable from the session's own
 * timestamps; the event does not try to characterise it, because "abandoned
 * halfway" and "cancelled the day before" are the same decision made at
 * different moments.
 */
final readonly class PlaytestSessionCancelled
{
    public function __construct(
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public string $cancelledBy,
        public DateTimeImmutable $cancelledAt,
    ) {}
}
