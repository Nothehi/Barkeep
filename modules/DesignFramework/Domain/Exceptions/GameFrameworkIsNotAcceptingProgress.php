<?php

namespace Modules\DesignFramework\Domain\Exceptions;

use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;

/**
 * Raised when a game tries to record framework work it is not currently doing.
 *
 * Three things can close the door, and every write has to clear all of them: the
 * game may have been archived, the adoption may be paused, and the adoption may
 * be complete. The first is GameDesign's rule and is delegated rather than
 * reimplemented; the other two are this module's.
 *
 * Reading is unaffected in every case. A completed adoption keeps every
 * evaluation, completion and response it gathered — that record is the reason to
 * have worked the framework at all.
 */
final class GameFrameworkIsNotAcceptingProgress extends FrameworkRuleViolation
{
    private function __construct(public readonly ?GameFrameworkStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(GameFrameworkStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the adoption is fine but the game around it is not.
     *
     * The message comes from GameDesign, so a designer is told the real reason —
     * "this game has been archived" — rather than a guess made here.
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
