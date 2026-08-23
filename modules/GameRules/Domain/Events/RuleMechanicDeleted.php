<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set stops claiming to use a mechanism.
 */
final readonly class RuleMechanicDeleted
{
    public function __construct(
        public string $mechanicId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
