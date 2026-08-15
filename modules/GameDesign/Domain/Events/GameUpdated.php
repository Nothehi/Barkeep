<?php

namespace Modules\GameDesign\Domain\Events;

/**
 * Dispatched when a game's own metadata changes.
 *
 * Lifecycle and design-phase moves have their own events; this one is only
 * about the name, address and description.
 */
final readonly class GameUpdated
{
    /**
     * @param  list<string>  $changed  The attributes that actually changed.
     */
    public function __construct(
        public string $gameId,
        public string $workspaceId,
        public string $updatedBy,
        public array $changed,
    ) {}
}
