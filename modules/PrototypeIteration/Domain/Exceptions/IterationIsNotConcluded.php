<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when a new design version is cut from a cycle that has not finished.
 *
 * The ordering rule behind section 48's optional action. Cutting a game version says
 * "the design has moved on, and here is what it moved to" — a claim that rests on the
 * cycle's conclusions. An open cycle has not reached any: its decisions may still be
 * rejected and its changes may still be reversed, so a version cut from it would be a
 * statement about work that might yet be abandoned.
 *
 * A 409 rather than a validation error, because nothing about the request is wrong. The
 * caller asked for something reasonable at the wrong moment, and the fix is to finish
 * the iteration rather than to correct a field.
 */
final class IterationIsNotConcluded extends IterationRuleViolation
{
    private function __construct(public readonly string $iterationId, string $message)
    {
        parent::__construct($message);
    }

    public static function forIteration(string $iterationId): self
    {
        return new self(
            $iterationId,
            __('Complete this iteration before cutting a new game version from it.'),
        );
    }

    public function status(): int
    {
        return 409;
    }
}
