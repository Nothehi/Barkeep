<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a resource's configuration changes.
 *
 * Carries which fields moved rather than the values themselves. A consumer that
 * needs the numbers can read them; a consumer deciding whether to care — "did
 * the cap change, or just the description?" — only needs the names, and shipping
 * the values would put a copy of this module's data in every listener.
 */
final readonly class ResourceTypeUpdated
{
    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public string $resourceId,
        public string $profileId,
        public array $changedFields,
    ) {}
}
