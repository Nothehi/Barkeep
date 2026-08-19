<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The resources a configuration declares, in the designer's own order.
 *
 * @see EconomyRepository::resourcesOf()
 */
final class GetResources
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, ResourceType>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->economy->resourcesOf($profile);
    }
}
