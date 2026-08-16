<?php

namespace Modules\Playtesting\Domain\Events;

/**
 * Dispatched when a participant's feedback is recorded.
 *
 * The rating is null more often than not, and a consumer averaging scores has
 * to skip those rather than read them as zero — a player who did not put a
 * number on their comment did not rate the game badly.
 */
final readonly class FeedbackCreated
{
    public function __construct(
        public string $feedbackId,
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public ?string $participantId,
        public ?int $rating,
        public string $createdBy,
    ) {}
}
