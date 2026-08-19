<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a tunable number changes.
 *
 * The event a studio's history is most likely to be reconstructed from — "when
 * did starting gold go from 10 to 12?" — which is why the field names travel
 * even though the values do not. A consumer that wants the numbers reads the
 * snapshots, which is what snapshots are for.
 */
final readonly class BalanceVariableUpdated
{
    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public string $variableId,
        public string $profileId,
        public array $changedFields,
    ) {}
}
