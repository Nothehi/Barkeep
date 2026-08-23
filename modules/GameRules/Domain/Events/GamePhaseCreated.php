<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a stage of play is added to a rule set.

 * A phase of the *game*, not of the designer's work — see `GamePhase`.
 */
final readonly class GamePhaseCreated
{
    public function __construct(
        public string $phaseId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
