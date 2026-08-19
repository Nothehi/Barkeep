<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when an action changes.
 *
 * Covers what the action *does* as well as what it is called: adding a cost,
 * retuning a reward or recording an effect all fire this, because to anything
 * outside the module those are changes to the action rather than to a row in a
 * table it has never heard of.
 *
 * @see ResourceTypeUpdated for why only the field names travel.
 */
final readonly class EconomyActionUpdated
{
    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public string $actionId,
        public string $profileId,
        public array $changedFields,
    ) {}
}
