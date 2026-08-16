<?php

namespace Modules\GameDesign\Domain\Events;

use Modules\GameDesign\Domain\Enums\DesignPhase;

/**
 * Dispatched when a game moves through the design process.
 *
 * Distinct from GameStatusChanged on purpose: this is progress through the
 * design, not through the project. A game can reach playtesting and then be
 * put on hold without either event implying the other.
 *
 * Movement is not necessarily forwards — dropping back from playtesting to
 * prototyping is a normal part of designing a game.
 */
final readonly class GameDesignPhaseChanged
{
    public function __construct(
        public string $gameId,
        public string $workspaceId,
        public string $changedBy,
        public DesignPhase $from,
        public DesignPhase $to,
    ) {}
}
