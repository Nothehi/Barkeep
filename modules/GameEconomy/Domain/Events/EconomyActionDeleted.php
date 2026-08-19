<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when an action is removed from a configuration.
 *
 * Its costs, rewards and effects go with it — they describe the action and
 * nothing else — so a consumer should treat this as the removal of everything
 * that action did.
 */
final readonly class EconomyActionDeleted
{
    public function __construct(
        public string $actionId,
        public string $profileId,
        public string $slug,
    ) {}
}
