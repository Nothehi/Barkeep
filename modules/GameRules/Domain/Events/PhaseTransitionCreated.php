<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a designer says how play moves between two phases.
 */
final readonly class PhaseTransitionCreated
{
    public function __construct(
        public string $transitionId,
        public string $ruleSetId,
        public string $fromPhaseId,
    ) {}
}
