<?php

namespace Modules\GameEconomy\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Events\BalanceProfileActivated;
use Modules\GameEconomy\Domain\Events\BalanceProfileArchived;
use Modules\GameEconomy\Domain\Exceptions\InvalidBalanceProfileTransition;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * Put a configuration into play.
 *
 * "These are the numbers now." A design state has exactly one active profile,
 * so activating one has to displace whichever was active before — and the only
 * legal move out of active is archived, which means activating a new
 * configuration retires the old one rather than demoting it back to a draft.
 *
 * That is the right behaviour rather than a consequence of the lifecycle. A
 * configuration that was in play while playtests ran is a historical record; a
 * draft is a work in progress. Turning the first back into the second would make
 * the numbers a session was played against editable again.
 *
 * ## Why this is a transaction with a lock
 *
 * The uniqueness is enforced by a partial index, so two simultaneous activations
 * cannot both succeed — one would fail with a constraint violation reaching the
 * designer as a 500. The row lock makes them queue instead, so the second one
 * sees the first's result and retires it properly.
 */
final class ActivateBalanceProfile
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, BalanceProfile $profile): BalanceProfile
    {
        $this->guard->ensureProfileIsModifiable($profile);

        $superseded = DB::transaction(function () use ($profile): ?BalanceProfile {
            /** @var BalanceProfile $locked */
            $locked = BalanceProfile::query()->lockForUpdate()->findOrFail($profile->getKey());

            if ($locked->status === BalanceProfileStatus::Active) {
                return null;
            }

            if (! $locked->status->canTransitionTo(BalanceProfileStatus::Active)) {
                throw InvalidBalanceProfileTransition::between($locked->status, BalanceProfileStatus::Active);
            }

            $current = BalanceProfile::query()
                ->where('game_version_id', $locked->game_version_id)
                ->where('status', BalanceProfileStatus::Active)
                ->whereKeyNot($locked->getKey())
                ->lockForUpdate()
                ->first();

            if ($current !== null) {
                $current->status = BalanceProfileStatus::Archived;
                $current->save();
            }

            $locked->status = BalanceProfileStatus::Active;
            $locked->save();

            $profile->status = BalanceProfileStatus::Active;

            return $current;
        });

        if ($superseded !== null) {
            event(new BalanceProfileArchived(
                profileId: $superseded->getKey(),
                gameVersionId: $superseded->game_version_id,
                archivedBy: $actor->id,
            ));
        }

        event(new BalanceProfileActivated(
            profileId: $profile->getKey(),
            gameVersionId: $profile->game_version_id,
            supersededProfileId: $superseded?->getKey(),
            activatedBy: $actor->id,
        ));

        return $profile;
    }
}
