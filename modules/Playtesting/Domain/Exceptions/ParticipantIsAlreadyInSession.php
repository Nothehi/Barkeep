<?php

namespace Modules\Playtesting\Domain\Exceptions;

/**
 * Raised when a registered account is added to a session it is already at.
 *
 * Somebody can only sit at a table once, and a session that lists the same
 * account twice reports two players where there was one — which quietly
 * inflates every participant count derived from it.
 *
 * The rule only reaches as far as identity can. A participant with an account
 * is recognisable, so their second row is refused both here and by a unique
 * index. Two guests both introduced as "Sam" are not the same claim: they may
 * genuinely be two people called Sam, and the platform has nothing to tell
 * them apart with. Guessing would be worse than allowing it.
 */
final class ParticipantIsAlreadyInSession extends PlaytestRuleViolation
{
    private function __construct(public readonly string $userId, string $message)
    {
        parent::__construct($message);
    }

    public static function forUser(string $userId): self
    {
        return new self($userId, __('That person is already a participant in this session.'));
    }

    public function status(): int
    {
        return 409;
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'user_id';
    }
}
