<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;

/**
 * One of a configuration's frozen states, by id.
 */
final class GetBalanceSnapshot
{
    public function __construct(private readonly BalanceProfileRepository $profiles) {}

    public function handle(BalanceProfile $profile, string $snapshotId): ?BalanceSnapshot
    {
        return $this->profiles->findSnapshotInProfile($profile, $snapshotId);
    }
}
