<?php

namespace Modules\GameEconomy\Domain\Exceptions;

use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;

/**
 * Raised when a scenario is asked to make a move its lifecycle does not allow.
 */
final class InvalidBalanceScenarioTransition extends EconomyRuleViolation
{
    private function __construct(
        public readonly BalanceScenarioStatus $from,
        public readonly BalanceScenarioStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(BalanceScenarioStatus $from, BalanceScenarioStatus $to): self
    {
        return new self($from, $to, __('A :from scenario cannot become :to.', [
            'from' => $from->label(),
            'to' => $to->label(),
        ]));
    }

    public function status(): int
    {
        return 409;
    }
}
