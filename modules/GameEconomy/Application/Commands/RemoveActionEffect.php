<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\Identity\Domain\Models\User;

/**
 * Remove something an action did beyond moving resources.
 */
final class RemoveActionEffect
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ActionEffect $effect): void
    {
        $action = $effect->action;
        $profile = $action?->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $actionId = $effect->action_id;
        $profileId = $action->balance_profile_id;

        $effect->delete();

        event(new EconomyActionUpdated(
            actionId: $actionId,
            profileId: $profileId,
            changedFields: ['effects'],
        ));
    }
}
