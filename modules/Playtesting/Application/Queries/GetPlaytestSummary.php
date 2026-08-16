<?php

namespace Modules\Playtesting\Application\Queries;

use Modules\Playtesting\Application\DTOs\PlaytestSummary;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * Everything a playtest has produced, counted on read.
 *
 * A query rather than an analytics subsystem, and that is the whole design
 * decision. Nothing is persisted, no rollup table exists and no listener
 * maintains a counter — because the moment a stored count and the rows it
 * describes can disagree, somebody spends an afternoon finding out which one
 * is lying.
 *
 * It is affordable because the numbers are small: a playtest has a handful of
 * sessions and a session has a handful of people. When somebody eventually
 * wants figures across hundreds of playtests, that is a reporting problem for
 * the analytics capability rather than a reason to denormalise this.
 */
final class GetPlaytestSummary
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    public function handle(Playtest $playtest): PlaytestSummary
    {
        return $this->playtests->summarise($playtest);
    }
}
