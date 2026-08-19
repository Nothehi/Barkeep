<?php

namespace Modules\GameEconomy\Domain\Events;

use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;

/**
 * Dispatched when a designer exposes a number for tuning.
 */
final readonly class BalanceVariableCreated
{
    public function __construct(
        public string $variableId,
        public string $profileId,
        public string $slug,
        public BalanceVariableCategory $category,
    ) {}
}
