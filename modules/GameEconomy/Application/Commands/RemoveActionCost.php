<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\Identity\Domain\Models\User;

/**
 * Stop an action costing a resource.
 *
 * Nothing points at a cost line, so there is nothing to refuse. The consequence
 * shows up in the analysis: an action with no costs left is reported as free,
 * which is the reminder somebody wants after removing the last one by accident.
 */
final class RemoveActionCost
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ActionCost $cost): void
    {
        $action = $cost->action;
        $profile = $action?->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $actionId = $cost->action_id;
        $profileId = $action->balance_profile_id;

        $cost->delete();

        event(new EconomyActionUpdated(
            actionId: $actionId,
            profileId: $profileId,
            changedFields: ['costs'],
        ));
    }
}
