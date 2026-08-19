<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\DTOs\IterationFilters;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;

/**
 * The design cycles of a game, newest first.
 *
 * Always game-scoped, with the game as a required argument rather than a filter, so there
 * is no "all iterations" query to reach for by mistake.
 *
 * Each row carries its four counts, which is what makes the iterations list readable at a
 * glance: "three changes, two experiments, one decision, four playtests" tells a designer
 * how substantial a cycle was without opening it, and the alternative — counting on the
 * client from loaded relations — would mean shipping every change and decision in the game
 * to draw a list.
 *
 * @see IterationRepository::forGame()
 */
final class GetIterations
{
    public function __construct(private readonly IterationRepository $iterations) {}

    /**
     * @return Collection<int, Iteration>
     */
    public function handle(Game $game, ?IterationFilters $filters = null): Collection
    {
        return $this->iterations->forGame($game, $filters);
    }
}
