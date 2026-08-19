<?php

namespace Modules\GameDesign\Application\DTOs;

use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;

/**
 * What a game's home screen is made of.
 *
 * The game itself, what has been decided about its design, how many
 * iterations it has been through, and which one is current. The design record
 * is here because it is the largest thing the platform actually knows about a
 * game and the overview was the one screen not saying it — a designer had to
 * open the settings form to read back their own pitch.
 *
 * Playtest counts, feedback summaries, balance readings and progress toward a
 * goal still belong to contexts that do not exist yet, and inventing
 * placeholder numbers for them now would be inventing the answers too.
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

        /**
         * Null when the designer has decided nothing, which is most games.
         * The overview draws an invitation from that rather than a record full
         * of nulls, so "not decided" stays distinguishable from "decided to
         * leave blank".
         */
        public ?DesignRecord $designRecord = null,
    ) {}

    /**
     * Determine whether the game has been through any iterations yet.
     */
    public function hasVersions(): bool
    {
        return $this->versionCount > 0;
    }
}
