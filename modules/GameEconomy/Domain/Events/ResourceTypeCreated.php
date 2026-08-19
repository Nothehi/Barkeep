<?php

namespace Modules\GameEconomy\Domain\Events;

use Modules\GameEconomy\Domain\Enums\ResourceCategory;

/**
 * Dispatched when a designer declares something players hold and spend.
 *
 * The category travels with it because it is the field that makes the event mean
 * different things to different consumers — a new currency and a new victory
 * track are very different additions to an economy.
 */
final readonly class ResourceTypeCreated
{
    public function __construct(
        public string $resourceId,
        public string $profileId,
        public string $slug,
        public ResourceCategory $category,
    ) {}
}
