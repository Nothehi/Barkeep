<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The values a hypothetical states differently, with the numbers they replace.
 *
 * The base variable travels with each override, because an override on its own
 * says nothing: "15" is only a scenario when you can see that the profile says
 * 10.
 */
final class GetScenarioVariables
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, ScenarioVariable>
     */
    public function handle(BalanceScenario $scenario): Collection
    {
        return $this->economy->overridesOf($scenario);
    }
}
