<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a tunable number is removed from a configuration.
 *
 * Every scenario override of it goes too, because an override of a variable that
 * no longer exists is a number about nothing.
 */
final readonly class BalanceVariableDeleted
{
    public function __construct(
        public string $variableId,
        public string $profileId,
        public string $slug,
    ) {}
}
