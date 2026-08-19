<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\BalanceVariableDeleted;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a tunable number from a configuration.
 *
 * Every scenario override of it goes too, by cascade. That is the correct
 * reading rather than a convenience: an override of a variable that no longer
 * exists is a number about nothing, and keeping it would make a scenario appear
 * to change something it does not.
 */
final class DeleteBalanceVariable
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, BalanceVariable $variable): void
    {
        $profile = $variable->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $variableId = $variable->getKey();
        $profileId = $variable->balance_profile_id;
        $slug = $variable->slug;

        $variable->delete();

        event(new BalanceVariableDeleted(
            variableId: $variableId,
            profileId: $profileId,
            slug: $slug,
        ));
    }
}
