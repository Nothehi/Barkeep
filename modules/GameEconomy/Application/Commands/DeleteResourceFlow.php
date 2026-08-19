<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\ResourceFlowDeleted;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a declared movement of a resource.
 *
 * Nothing points at a flow, so there is no usage check and nothing to refuse.
 * The consequence is real though, and the analysis will say so on the next read:
 * removing the only generation flow for a resource turns it into one nothing
 * produces, which is reported as an error.
 */
final class DeleteResourceFlow
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ResourceFlow $flow): void
    {
        $profile = $flow->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $flowId = $flow->getKey();
        $profileId = $flow->balance_profile_id;
        $resourceId = $flow->resource_type_id;

        $flow->delete();

        event(new ResourceFlowDeleted(
            flowId: $flowId,
            profileId: $profileId,
            resourceId: $resourceId,
        ));
    }
}
