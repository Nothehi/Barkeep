<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * One of a configuration's tunable numbers, by id.
 */
final class GetBalanceVariable
{
    public function __construct(private readonly EconomyRepository $economy) {}

    public function handle(BalanceProfile $profile, string $variableId): ?BalanceVariable
    {
        return $this->economy->findVariableInProfile($profile, $variableId);
    }
}
