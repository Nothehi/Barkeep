<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * What an action pays out.
 */
final class GetActionRewards
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, ActionReward>
     */
    public function handle(EconomyAction $action): Collection
    {
        return $this->economy->rewardsOf($action);
    }
}
