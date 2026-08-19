<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\DTOs\PrototypeFilters;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * The prototypes of a game.
 *
 * Always game-scoped, and the game is a required argument rather than a filter — there is
 * no "all prototypes" query to call by mistake. Filters can only narrow what a caller
 * could already see.
 *
 * Resolution is unauthorized on purpose: finding the prototypes and deciding who may see
 * them are separate steps, and every caller runs the policy against the game first.
 * Merging the two would make it easy to forget the second half.
 *
 * @see PrototypeRepository::forGame()
 */
final class GetPrototypes
{
    public function __construct(private readonly PrototypeRepository $prototypes) {}

    /**
     * @return Collection<int, Prototype>
     */
    public function handle(Game $game, ?PrototypeFilters $filters = null): Collection
    {
        return $this->prototypes->forGame($game, $filters);
    }
}
