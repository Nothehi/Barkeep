<?php

namespace Modules\GameDesign\Application\Queries;

use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;

/**
 * What has been decided about a game's design.
 *
 * Always found through the game, never by id, so the workspace scoping that
 * produced the game has already happened and cannot be skipped here.
 *
 * Null is an ordinary answer rather than an error: most games have decided
 * nothing, and a screen asking this is usually the one offering to record the
 * first thing. Returning an empty record instead would make "nothing decided"
 * and "a record that says nothing" indistinguishable, and the difference is what
 * a methodology's factual criteria are reading.
 */
final class GetDesignRecord
{
    public function __construct(private readonly GameRepository $games) {}

    public function handle(Game $game): ?DesignRecord
    {
        return $this->games->designRecordOf($game);
    }
}
