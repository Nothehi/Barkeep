<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a declared movement of a resource is removed.
 */
final readonly class ResourceFlowDeleted
{
    public function __construct(
        public string $flowId,
        public string $profileId,
        public string $resourceId,
    ) {}
}
