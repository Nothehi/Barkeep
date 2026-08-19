<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;

/**
 * One of a game's design cycles, by id.
 *
 * Takes a resolved game rather than a game id, so the workspace scoping that produced it
 * has already happened and cannot be skipped here. An iteration id from a different game
 * returns null and the route binding turns that into a 404 — before any handler or policy
 * runs.
 */
final class GetIteration
{
    public function __construct(private readonly IterationRepository $iterations) {}

    public function handle(Game $game, string $iterationId): ?Iteration
    {
        return $this->iterations->findForGame($game, $iterationId);
    }
}
