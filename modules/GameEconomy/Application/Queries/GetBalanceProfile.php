<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;

/**
 * One of a design state's balance configurations, by id.
 *
 * What the route binding calls. Scoped to the version, so a profile belonging to
 * another design state fails to resolve rather than being caught later by a
 * policy — which is what lets a profile id be an opaque uuid in a URL without
 * being a capability.
 */
final class GetBalanceProfile
{
    public function __construct(private readonly BalanceProfileRepository $profiles) {}

    public function handle(GameVersion $version, string $profileId): ?BalanceProfile
    {
        return $this->profiles->findForVersion($version, $profileId);
    }
}
