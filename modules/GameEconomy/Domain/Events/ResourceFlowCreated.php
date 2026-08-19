<?php

namespace Modules\GameEconomy\Domain\Events;

use Modules\GameEconomy\Domain\Enums\ResourceFlowType;

/**
 * Dispatched when a designer declares a way a resource moves.
 */
final readonly class ResourceFlowCreated
{
    public function __construct(
        public string $flowId,
        public string $profileId,
        public string $resourceId,
        public ResourceFlowType $flowType,
    ) {}
}
