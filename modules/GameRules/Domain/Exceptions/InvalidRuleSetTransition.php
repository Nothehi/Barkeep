<?php

namespace Modules\GameRules\Domain\Exceptions;

use Modules\GameRules\Domain\Enums\RuleSetStatus;

/**
 * Raised when a rule set is asked to move somewhere its lifecycle does not go.
 *
 * In practice this means un-archiving. Archived is terminal, so the only way
 * back to a working rule system is a clone — which is also how a designer would
 * describe what they are doing.
 */
final class InvalidRuleSetTransition extends RuleSystemViolation
{
    private function __construct(
        public readonly RuleSetStatus $from,
        public readonly RuleSetStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(RuleSetStatus $from, RuleSetStatus $to): self
    {
        return new self($from, $to, __('A :from rule set cannot become :to.', [
            'from' => $from->label(),
            'to' => $to->label(),
        ]));
    }

    public function status(): int
    {
        return 409;
    }
}
