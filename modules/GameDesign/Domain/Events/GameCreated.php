<?php

namespace Modules\GameDesign\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched once a new game exists.
 *
 * Carries ids only. Consumers that need the game itself resolve it through
 * GameDesign's own query layer rather than reaching into its tables, which is
 * what keeps the workspace scoping in one place.
 *
 * Nothing is subscribed to this yet. It exists now so that Gamification,
 * Analytics and Notification can be built later without GameDesign learning
 * their names.
 */
final readonly class GameCreated
{
    public function __construct(
        public string $gameId,
        public string $workspaceId,
        public string $createdBy,
        public string $slug,
        public CarbonImmutable $createdAt,
    ) {}
}
