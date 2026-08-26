<?php

namespace Modules\GameDesign\Application\DTOs;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;

/**
 * What a studio's design work adds up to, across every game in a workspace.
 *
 * The game-level equivalent of this already exists in {@see GameDashboard};
 * this is the same idea one level up, for the screen somebody lands on after
 * signing in. It answers "what is going on here?" rather than "what is going
 * on with this game?".
 *
 * Every figure is derived on read. Nothing is persisted, no rollup table
 * exists and no listener maintains a counter — the reason is the one that
 * applies everywhere in this platform: the moment a stored count and the rows
 * it describes can disagree, somebody spends an afternoon finding out which
 * one is lying. It is affordable because a workspace holds a handful of games.
 *
 * The two tallies are complete rather than sparse: every status and every
 * phase is present, including the ones nothing is sitting in. A distribution
 * with holes in it cannot be drawn, and "no game has reached production" is
 * itself worth reading.
 */
final readonly class WorkspaceDesignActivity
{
    /**
     * @param  array<string, int>  $gamesByStatus  keyed by GameStatus value, in enum order
     * @param  array<string, int>  $gamesByDesignPhase  keyed by DesignPhase value, in enum order
     * @param  Collection<int, Game>  $recentGames  most recently worked on first
     */
    public function __construct(
        public int $gameCount,
        public int $versionCount,
        public array $gamesByStatus,
        public array $gamesByDesignPhase,
        public Collection $recentGames,
    ) {}

    /**
     * Determine whether anything has been designed here yet.
     *
     * The question behind whether the dashboard draws a summary or an
     * invitation to start the first game.
     */
    public function hasGames(): bool
    {
        return $this->gameCount > 0;
    }
}
