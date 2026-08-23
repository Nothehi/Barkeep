<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when a phase transition would not move play.
 *
 * A transition from a phase to itself is the only shape refused outright, and it
 * is refused because it is never what somebody meant: play arriving where it
 * already is has not advanced, and a graph drawn from it grows a self-loop that
 * makes every other arrow harder to read. A phase that genuinely repeats is a
 * round, and the transition belongs on the round's boundary.
 *
 * Transitions crossing rule sets raise {@see RecordDoesNotBelongToRuleSet}
 * instead: those two ends are separately unresolvable, so the refusal names
 * which one was wrong.
 */
final class InvalidPhaseTransition extends RuleSystemViolation
{
    private function __construct(public readonly string $phaseId, string $message)
    {
        parent::__construct($message);
    }

    public static function loopsOnItself(string $phaseId): self
    {
        return new self($phaseId, __('A transition has to lead to a different phase.'));
    }

    public function field(): string
    {
        return 'to_phase_id';
    }
}
