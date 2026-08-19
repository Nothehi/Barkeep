<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * One of a configuration's actions, by id.
 */
final class GetEconomyAction
{
    public function __construct(private readonly EconomyRepository $economy) {}

    public function handle(BalanceProfile $profile, string $actionId): ?EconomyAction
    {
        return $this->economy->findActionInProfile($profile, $actionId);
    }
}
