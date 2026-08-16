<?php

namespace Modules\Playtesting\Domain\Exceptions;

use Modules\Playtesting\Domain\Enums\PlaytestStatus;

/**
 * Raised when something tries to change a playtest that has been closed.
 *
 * Enforced in the application layer rather than only in the policy. The policy
 * stops the request; this stops the *operation*, including any future caller
 * that reaches a command without going through HTTP.
 *
 * There are three ways to be closed and the message says which: the playtest
 * itself is over, the game it belongs to has been archived, or the caller is
 * trying to rewrite the plan of a playtest that has already produced evidence.
 */
final class PlaytestIsNotModifiable extends PlaytestRuleViolation
{
    /**
     * @param  PlaytestStatus|null  $status  the playtest's own status, when that is what refused
     */
    private function __construct(public readonly ?PlaytestStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(PlaytestStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the playtest is fine but the game around it is not.
     *
     * A playtest of an archived game is as frozen as a completed one, and for
     * the same reason: the thing it is evidence about has stopped changing.
     * Reading it is still allowed — that is the point of keeping it.
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
