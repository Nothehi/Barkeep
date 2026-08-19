<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * What an action does that is not a quantity of a resource.
 */
final class GetActionEffects
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, ActionEffect>
     */
    public function handle(EconomyAction $action): Collection
    {
        return $this->economy->effectsOf($action);
    }
}
