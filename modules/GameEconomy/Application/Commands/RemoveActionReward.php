<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\Identity\Domain\Models\User;

/**
 * Stop an action paying out a resource.
 */
final class RemoveActionReward
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ActionReward $reward): void
    {
        $action = $reward->action;
        $profile = $action?->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $actionId = $reward->action_id;
        $profileId = $action->balance_profile_id;

        $reward->delete();

        event(new EconomyActionUpdated(
            actionId: $actionId,
            profileId: $profileId,
            changedFields: ['rewards'],
        ));
    }
}
