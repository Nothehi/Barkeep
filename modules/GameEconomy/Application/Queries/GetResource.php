<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * One of a configuration's resources, by id.
 *
 * What the route binding calls. Scoped to the profile, so a resource from
 * another configuration is never found.
 */
final class GetResource
{
    public function __construct(private readonly EconomyRepository $economy) {}

    public function handle(BalanceProfile $profile, string $resourceId): ?ResourceType
    {
        return $this->economy->findResourceInProfile($profile, $resourceId);
    }
}
