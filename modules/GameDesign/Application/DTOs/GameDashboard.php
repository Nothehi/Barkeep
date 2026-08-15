<?php

namespace Modules\GameDesign\Application\DTOs;

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;

/**
 * What a game's home screen is made of.
 *
 * Deliberately thin: the game itself, how many iterations it has been
 * through, and which one is current. Playtest counts, feedback summaries,
 * balance readings and progress toward a goal all belong to contexts that do
 * not exist yet, and inventing placeholder numbers for them now would be
 * inventing the answers too.
 *
 * It exists as a type rather than as a loose array so that when those
 * contexts do arrive, there is one place that says what a game's overview
 * consists of.
 */
final readonly class GameDashboard
{
    public function __construct(
        public Game $game,
        public int $versionCount,
        public ?GameVersion $latestVersion = null,
    ) {}

    /**
     * Determine whether the game has been through any iterations yet.
     */
    public function hasVersions(): bool
    {
        return $this->versionCount > 0;
    }
}
