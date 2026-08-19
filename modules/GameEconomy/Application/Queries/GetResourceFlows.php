<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The declared movements of a configuration's resources.
 */
final class GetResourceFlows
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, ResourceFlow>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->economy->flowsOf($profile);
    }
}
