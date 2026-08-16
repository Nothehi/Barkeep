<?php

namespace Modules\Playtesting\Domain\Exceptions;

/**
 * Raised when evidence is attributed to somebody who was not at the session.
 *
 * Observations and feedback may name a participant, and that participant id
 * arrives in a request body rather than through a route binding — so it is the
 * one identifier in the module that is not scoped by resolution and has to be
 * checked explicitly.
 *
 * Getting this wrong would be worse than a leak. Attaching one session's
 * feedback to another session's participant produces a record that reads
 * perfectly and is false.
 */
final class ParticipantDoesNotBelongToSession extends PlaytestRuleViolation
{
    private function __construct(
        public readonly string $participantId,
        public readonly string $sessionId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $participantId, string $sessionId): self
    {
        return new self($participantId, $sessionId, __('That participant is not part of this session.'));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'participant_id';
    }
}
