<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a declared movement of a resource changes.
 *
 * @see ResourceTypeUpdated for why only the field names travel.
 */
final readonly class ResourceFlowUpdated
{
    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public string $flowId,
        public string $profileId,
        public array $changedFields,
    ) {}
}
