<?php

namespace Modules\Playtesting\Application\DTOs;

use Illuminate\Database\Eloquent\Collection;
use Modules\Playtesting\Domain\Models\Playtest;

/**
 * What a studio has found out, across every game in a workspace.
 *
 * The module's contribution to the app's home screen, and the thing the game
 * overview still cannot say: how much of the design has actually met a table.
 * A workspace with eleven games and no playtests is a very different place
 * from one with three games and forty sittings, and the dashboard is where
 * that difference is worth reading.
 *
 * Every figure is derived on read, for the reason given in
 * {@see PlaytestSummary}: a stored count that can disagree with the rows it
 * describes costs somebody an afternoon. It is affordable because the numbers
 * are small.
 *
 * The tally is complete rather than sparse — every status is present, zero
 * included — because a distribution with holes in it cannot be drawn, and "no
 * playtest has been cancelled" is itself worth reading.
 */
final readonly class WorkspacePlaytestActivity
{
    /**
     * @param  array<string, int>  $playtestsByStatus  keyed by PlaytestStatus value, in enum order
     * @param  Collection<int, Playtest>  $recentPlaytests  most recently planned first, each with its game
     */
    public function __construct(
        public int $playtestCount,
        public int $sessionCount,
        public array $playtestsByStatus,
        public Collection $recentPlaytests,
    ) {}

    /**
     * Determine whether anything has been tested here yet.
     *
     * The question behind whether the dashboard draws a summary or an
     * invitation to plan the first playtest.
     */
    public function hasPlaytests(): bool
    {
        return $this->playtestCount > 0;
    }
}
