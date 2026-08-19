<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;

/**
 * The beliefs a configuration's numbers were chosen to satisfy.
 *
 * Least-confident first, so the things worth going and testing are not buried
 * under the settled ones.
 */
final class GetBalanceAssumptions
{
    public function __construct(private readonly BalanceProfileRepository $profiles) {}

    /**
     * @return Collection<int, BalanceAssumption>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->profiles->assumptionsOf($profile);
    }
}
