<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Events\BalanceProfileArchived;
use Modules\GameEconomy\Domain\Exceptions\InvalidBalanceProfileTransition;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * Put a configuration away for good.
 *
 * Irreversible, and that is the point rather than an inconvenience. A profile a
 * playtest was run against must not be able to become the current one again,
 * because every observation filed against it would start describing numbers that
 * had changed underneath. A studio returning to an old shape copies it into a
 * new draft — which is also how they would describe it out loud.
 *
 * Archiving does not hide anything. The configuration stays readable, which is
 * the whole reason historical balance is worth keeping.
 */
final class ArchiveBalanceProfile
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, BalanceProfile $profile): BalanceProfile
    {
        $this->guard->ensureProfileIsModifiable($profile);

        if (! $profile->status->canTransitionTo(BalanceProfileStatus::Archived)) {
            throw InvalidBalanceProfileTransition::between($profile->status, BalanceProfileStatus::Archived);
        }

        $profile->status = BalanceProfileStatus::Archived;
        $profile->save();

        event(new BalanceProfileArchived(
            profileId: $profile->getKey(),
            gameVersionId: $profile->game_version_id,
            archivedBy: $actor->id,
        ));

        return $profile;
    }
}
