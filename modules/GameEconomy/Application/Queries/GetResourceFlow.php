<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * One of a configuration's declared movements, by id.
 */
final class GetResourceFlow
{
    public function __construct(private readonly EconomyRepository $economy) {}

    public function handle(BalanceProfile $profile, string $flowId): ?ResourceFlow
    {
        return $this->economy->findFlowInProfile($profile, $flowId);
    }
}
