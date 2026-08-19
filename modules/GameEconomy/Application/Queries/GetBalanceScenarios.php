<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The hypotheticals a configuration is read under.
 */
final class GetBalanceScenarios
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, BalanceScenario>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->economy->scenariosOf($profile);
    }
}
