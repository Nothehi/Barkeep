<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when an iteration is completed without saying how it went.
 *
 * Both an outcome and a summary are required to close a cycle, and the
 * requirement is the point rather than a form nicety. An iteration with no
 * outcome is a period of time; the outcome and the summary are what make it a
 * turn of a loop that the next turn can be built on.
 *
 * The form request checks this first, so a caller filling in the completion
 * dialog is told which field is missing. This exists for every other route in:
 * an API client, a console command, a later module.
 */
final class IterationNeedsAnOutcome extends IterationRuleViolation
{
    private function __construct(public readonly string $iterationId, string $message)
    {
        parent::__construct($message);
    }

    public static function forIteration(string $iterationId): self
    {
        return new self($iterationId, __('Say how the iteration turned out and what you learned before completing it.'));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'outcome';
    }
}
