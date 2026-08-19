<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * One of a configuration's hypotheticals, by id.
 */
final class GetScenario
{
    public function __construct(private readonly EconomyRepository $economy) {}

    public function handle(BalanceProfile $profile, string $scenarioId): ?BalanceScenario
    {
        return $this->economy->findScenarioInProfile($profile, $scenarioId);
    }
}
