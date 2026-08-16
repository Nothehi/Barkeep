<?php

namespace Modules\GameDesign\Domain\Events;

/**
 * Dispatched when a new iteration of a game is recorded.
 *
 * The version number is the one the module allocated, not one a caller asked
 * for, so consumers can rely on it being the game's highest at the moment the
 * event was raised.
 */
final readonly class GameVersionCreated
{
    public function __construct(
        public string $versionId,
        public string $gameId,
        public string $workspaceId,
        public int $versionNumber,
        public string $createdBy,
    ) {}
}
