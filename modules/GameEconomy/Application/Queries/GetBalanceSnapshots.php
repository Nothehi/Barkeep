<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;

/**
 * A configuration's frozen states, newest first.
 */
final class GetBalanceSnapshots
{
    public function __construct(private readonly BalanceProfileRepository $profiles) {}

    /**
     * @return Collection<int, BalanceSnapshot>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->profiles->snapshotsOf($profile);
    }
}
