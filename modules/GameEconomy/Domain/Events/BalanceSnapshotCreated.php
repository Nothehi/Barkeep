<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a configuration is frozen.
 *
 * The event that marks a point a studio can compare against afterwards, which is
 * why the counts travel with it: a consumer building a timeline wants to show
 * "8 resources, 14 actions" beside the entry without reading the payload back
 * out of the database.
 */
final readonly class BalanceSnapshotCreated
{
    /**
     * @param  array<string, int>  $tally
     */
    public function __construct(
        public string $snapshotId,
        public string $profileId,
        public array $tally,
        public string $createdBy,
    ) {}
}
