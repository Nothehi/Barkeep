<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceProfileData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Exceptions\EconomyNameIsTaken;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Change what a configuration is called and what it is for.
 *
 * Only the profile's own details. Nothing here touches a resource, an action or
 * a number — those have their own commands, gated on their own ability, so that
 * a future state which froze the configuration while still allowing the
 * description to be corrected would need no new seam.
 *
 * There is no status field. Activating and archiving are actions with rules and
 * their own endpoints, which is what keeps an irreversible move from being one
 * field value away from a reversible one.
 */
final class UpdateBalanceProfile
{
    public function __construct(
        private readonly BalanceProfileRepository $profiles,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, BalanceProfile $profile, BalanceProfileData $data): BalanceProfile
    {
        $this->guard->ensureProfileIsModifiable($profile);

        if ($data->name !== null && $data->name !== $profile->name) {
            $version = $profile->version;

            if ($version !== null && $this->profiles->versionHasProfileNamed($version, $data->name, $profile->getKey())) {
                throw EconomyNameIsTaken::forProfile($data->name);
            }

            $profile->name = $data->name;
        }

        /*
         * The description is only touched when the request mentioned it, so a
         * form that sends the name alone leaves it be — and one that sends an
         * empty description clears it. Collapsing those two would make a partial
         * update erase everything it did not name.
         */
        if ($data->descriptionWasSent) {
            $profile->description = $data->description;
        }

        $profile->save();

        return $profile;
    }
}
