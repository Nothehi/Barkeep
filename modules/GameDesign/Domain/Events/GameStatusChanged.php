<?php

namespace Modules\GameDesign\Domain\Events;

use Modules\GameDesign\Domain\Enums\GameStatus;

/**
 * Dispatched when a game moves through its project lifecycle.
 *
 * Both ends of the move are carried, because "became active" and "became
 * active again after being parked" are different things to anybody counting.
 */
final readonly class GameStatusChanged
{
    public function __construct(
        public string $gameId,
        public string $workspaceId,
        public string $changedBy,
        public GameStatus $from,
        public GameStatus $to,
    ) {}
}
