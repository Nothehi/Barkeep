<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a way for play to advance is removed.
 */
final readonly class PhaseTransitionDeleted
{
    public function __construct(
        public string $transitionId,
        public string $ruleSetId,
        public string $fromPhaseId,
    ) {}
}
