<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;

/**
 * The balance configurations of a design state.
 *
 * Always version-scoped, and the version is a required argument rather than a
 * filter — there is no "all profiles" query to call by mistake.
 *
 * Resolution is unauthorized on purpose: finding the configurations and deciding
 * who may see them are separate steps, and every caller runs the policy against
 * the profile or the version first. Merging the two would make it easy to forget
 * the second half.
 */
final class GetBalanceProfiles
{
    public function __construct(private readonly BalanceProfileRepository $profiles) {}

    /**
     * @return Collection<int, BalanceProfile>
     */
    public function handle(GameVersion $version): Collection
    {
        return $this->profiles->forVersion($version);
    }
}
