<?php

namespace Modules\GameEconomy\Domain\Exceptions;

use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;

/**
 * Raised when a scenario or one of its overrides is written to after archival.
 */
final class BalanceScenarioIsNotModifiable extends EconomyRuleViolation
{
    private function __construct(public readonly ?BalanceScenarioStatus $scenarioStatus, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(BalanceScenarioStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the profile around the scenario is what refused.
     */
    public static function becauseProfileIsClosed(string $reason): self
    {
        return new self(null, $reason);
    }

    public function status(): int
    {
        return 409;
    }
}
