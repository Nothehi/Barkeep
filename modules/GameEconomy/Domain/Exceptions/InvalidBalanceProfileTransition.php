<?php

namespace Modules\GameEconomy\Domain\Exceptions;

use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;

/**
 * Raised when a profile is asked to make a move its lifecycle does not allow.
 *
 * The move that matters is archived → active. A configuration a playtest ran
 * against must not be able to become the current one again, because every
 * observation filed against it would start describing numbers that had changed
 * underneath.
 */
final class InvalidBalanceProfileTransition extends EconomyRuleViolation
{
    private function __construct(
        public readonly BalanceProfileStatus $from,
        public readonly BalanceProfileStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(BalanceProfileStatus $from, BalanceProfileStatus $to): self
    {
        return new self($from, $to, __('A :from balance profile cannot become :to.', [
            'from' => $from->label(),
            'to' => $to->label(),
        ]));
    }

    public function status(): int
    {
        return 409;
    }
}
