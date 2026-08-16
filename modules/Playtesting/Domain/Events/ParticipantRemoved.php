<?php

namespace Modules\Playtesting\Domain\Events;

/**
 * Dispatched when somebody is taken off a session.
 *
 * Removal drops the attribution on anything they said or that was noticed
 * about them; it does not remove the evidence itself. A consumer that had
 * credited the participant for taking part should undo that, and should not
 * assume the session got smaller in any other way.
 */
final readonly class ParticipantRemoved
{
    public function __construct(
        public string $participantId,
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public ?string $userId,
        public string $removedBy,
    ) {}
}
