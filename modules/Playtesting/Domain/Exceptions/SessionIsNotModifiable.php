<?php

namespace Modules\Playtesting\Domain\Exceptions;

use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;

/**
 * Raised when something tries to change a session that is over.
 *
 * Covers the session's own details and everything hanging off it. Adding an
 * observation to a session that ended yesterday is refused here, and that
 * refusal is what makes a session's contents datable: everything in it was
 * recorded while the session was open.
 */
final class SessionIsNotModifiable extends PlaytestRuleViolation
{
    /**
     * @param  PlaytestSessionStatus|null  $status  the session's own status, when that is what refused
     */
    private function __construct(public readonly ?PlaytestSessionStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(PlaytestSessionStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the session is fine but the playtest or game around it is not.
     */
    public static function becausePlaytestIsClosed(string $reason): self
    {
        return new self(null, $reason);
    }

    public function status(): int
    {
        return 409;
    }
}
