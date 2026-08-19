<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;

/**
 * Raised when a decision is asked to move somewhere its lifecycle does not go.
 *
 * The set of legal moves lives on {@see DecisionStatus}, and this is the
 * strictest lifecycle in the module. Accepted → rejected is the move it exists
 * to refuse: reversing a settled decision in place would leave the design
 * carrying a change whose recorded justification now argues against it. The
 * message says what to do instead, because a caller hitting this has a real
 * intention and deserves the route to it.
 */
final class InvalidDecisionTransition extends IterationRuleViolation
{
    private function __construct(
        public readonly DecisionStatus $from,
        public readonly DecisionStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(DecisionStatus $from, DecisionStatus $to): self
    {
        return new self($from, $to, __('A :from decision cannot be moved to :to. Record a new decision instead.', [
            'from' => mb_strtolower($from->label()),
            'to' => mb_strtolower($to->label()),
        ]));
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
        return 'status';
    }
}
