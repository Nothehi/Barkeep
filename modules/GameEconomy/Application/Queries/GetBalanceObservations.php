<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;

/**
 * What the studio noticed about the economy, worst first.
 *
 * The opposite ordering to the assumptions beside it, and deliberately so: an
 * assumption list is read to find what has not been checked, an observation list
 * to find what is on fire.
 */
final class GetBalanceObservations
{
    public function __construct(private readonly BalanceProfileRepository $profiles) {}

    /**
     * @return Collection<int, BalanceObservation>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->profiles->observationsOf($profile);
    }
}
