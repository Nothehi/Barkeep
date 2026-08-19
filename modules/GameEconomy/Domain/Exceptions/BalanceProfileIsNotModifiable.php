<?php

namespace Modules\GameEconomy\Domain\Exceptions;

use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;

/**
 * Raised when anything is written to a profile that has been archived.
 *
 * Covers the whole configuration, not just the profile row: a resource, a flow,
 * an action, a cost, a variable and a scenario all belong to a profile, and an
 * archived profile refuses every one of them. Reading is untouched, which is
 * what keeps a two-year-old economy legible.
 */
final class BalanceProfileIsNotModifiable extends EconomyRuleViolation
{
    private function __construct(public readonly ?BalanceProfileStatus $profileStatus, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(BalanceProfileStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the game around the profile is what refused.
     *
     * Reported as a profile problem because that is the object the caller was
     * acting on, and carries the game's own wording so they are still told the
     * real reason.
     */
    public static function becauseGameIsClosed(string $reason): self
    {
        return new self(null, $reason);
    }

    public function status(): int
    {
        return 409;
    }
}
