<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a resource is removed from a configuration.
 *
 * Only ever fires for a resource nothing referenced — costs, rewards, flows and
 * variables all refuse to let one go — so a consumer may take this as "it was
 * never used" rather than as "eleven actions just became free".
 */
final readonly class ResourceTypeDeleted
{
    public function __construct(
        public string $resourceId,
        public string $profileId,
        public string $slug,
    ) {}
}
