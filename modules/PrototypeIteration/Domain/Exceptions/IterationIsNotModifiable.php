<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\IterationStatus;

/**
 * Raised when something is written to an iteration that is over.
 *
 * The historical integrity rule as a runtime refusal. A completed iteration's
 * plan, changes and decisions are what the next cycle was built on, so they stop
 * being editable when it closes — and a cancelled one is closed for the same
 * reason from the other direction.
 *
 * Carries the status's own wording, so the caller is told which of the two it is
 * and what that means rather than a bare refusal.
 */
final class IterationIsNotModifiable extends IterationRuleViolation
{
    private function __construct(public readonly IterationStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(IterationStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    public function status(): int
    {
        return 409;
    }
}
