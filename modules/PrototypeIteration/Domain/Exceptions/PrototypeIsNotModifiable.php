<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;

/**
 * Raised when something is written to a prototype that has been put away.
 *
 * Carries the status's own wording so the caller is told *why* rather than just
 * no. An archived prototype still reads — its versions are what a design history
 * points at — and refuses everything else.
 */
final class PrototypeIsNotModifiable extends IterationRuleViolation
{
    private function __construct(public readonly PrototypeStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(PrototypeStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    public function status(): int
    {
        return 409;
    }
}
