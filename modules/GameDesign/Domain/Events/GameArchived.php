<?php

namespace Modules\GameDesign\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched when a game is put away.
 *
 * Archival is the end of a game's editable life, not a deletion: consumers
 * should stop scheduling work against it but must not discard its history or
 * its versions.
 */
final readonly class GameArchived
{
    public function __construct(
        public string $gameId,
        public string $workspaceId,
        public string $archivedBy,
        public CarbonImmutable $archivedAt,
    ) {}
}
