<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The numbers a configuration exposes for tuning, grouped by what they do.
 */
final class GetBalanceVariables
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, BalanceVariable>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->economy->variablesOf($profile);
    }
}
