<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a phase is removed.

 * Its transitions go with it, because an edge with one end missing is not an edge.
 * The rules and actions that named it survive with the reference cleared, which
 * the validator then reports — an action with no phase is worth noticing, and
 * silently deleting somebody's action would not be.
 */
final readonly class GamePhaseDeleted
{
    public function __construct(
        public string $phaseId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
