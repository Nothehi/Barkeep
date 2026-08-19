<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * What an action takes to perform.
 */
final class GetActionCosts
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, ActionCost>
     */
    public function handle(EconomyAction $action): Collection
    {
        return $this->economy->costsOf($action);
    }
}
