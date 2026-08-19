<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionDeleted;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\Identity\Domain\Models\User;

/**
 * Remove an action from a configuration.
 *
 * Its costs, rewards and effects go with it — they describe this action and
 * nothing else, so cascading is the correct reading rather than a convenience.
 *
 * Variables that were *about* the action survive with their reference cleared,
 * which is deliberate: `build_cost` remains a number the studio tuned and argued
 * over, and deleting it because the action it annotated was renamed away would
 * throw out the reasoning along with the row.
 */
final class DeleteEconomyAction
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, EconomyAction $action): void
    {
        $profile = $action->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $actionId = $action->getKey();
        $profileId = $action->balance_profile_id;
        $slug = $action->slug;

        $action->delete();

        event(new EconomyActionDeleted(
            actionId: $actionId,
            profileId: $profileId,
            slug: $slug,
        ));
    }
}
