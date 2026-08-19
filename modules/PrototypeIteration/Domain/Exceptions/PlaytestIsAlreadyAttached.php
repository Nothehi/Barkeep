<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when the same playtest is attached to an iteration twice.
 *
 * Refused because the second attachment says nothing the first did not, and a
 * duplicate would make the "four playtests" on an iteration card a count of
 * button presses rather than of evidence.
 *
 * The database refuses it too, through a unique index, and that is the
 * authority: this is what a caller sees, but a double-submitted form is stopped
 * by the constraint whether or not the check above it ran.
 */
final class PlaytestIsAlreadyAttached extends IterationRuleViolation
{
    private function __construct(
        public readonly string $iterationId,
        public readonly string $playtestId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $iterationId, string $playtestId): self
    {
        return new self($iterationId, $playtestId, __('That playtest is already attached to this iteration.'));
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
        return 'playtest_id';
    }
}
