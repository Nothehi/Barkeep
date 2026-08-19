<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;

/**
 * The configuration currently in play for a design state.
 *
 * Null is an ordinary answer rather than an error: a version whose economy
 * nobody has started configuring has no active profile, and that is most of
 * them.
 *
 * At most one row can match. The partial unique index makes that a fact about
 * the data rather than an assumption about the query.
 */
final class GetActiveBalanceProfile
{
    public function __construct(private readonly BalanceProfileRepository $profiles) {}

    public function handle(GameVersion $version): ?BalanceProfile
    {
        return $this->profiles->activeForVersion($version);
    }
}
