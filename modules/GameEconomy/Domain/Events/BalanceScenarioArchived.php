<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a hypothetical is put away.
 */
final readonly class BalanceScenarioArchived
{
    public function __construct(
        public string $scenarioId,
        public string $profileId,
        public string $archivedBy,
    ) {}
}
