<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a studio names a situation to read the economy under.
 */
final readonly class BalanceScenarioCreated
{
    public function __construct(
        public string $scenarioId,
        public string $profileId,
        public string $createdBy,
    ) {}
}
