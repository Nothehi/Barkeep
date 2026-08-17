<?php

namespace Modules\GameDesign\Domain\Exceptions;

use Modules\GameDesign\Domain\Enums\MechanicStatus;

/**
 * Raised when a retired term is asked to change.
 *
 * Retirement is the end of a mechanic's editable life. Renaming or re-defining
 * something that has been withdrawn would change what it meant on the games
 * that claimed it back when it was offered, which is the one thing archiving
 * exists to avoid.
 */
final class MechanicIsNotModifiable extends GameRuleViolation
{
    private function __construct(public readonly MechanicStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(MechanicStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    public function status(): int
    {
        return 409;
    }
}
