<?php

namespace Modules\Playtesting\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\DTOs\PlaytestFilters;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * The playtests of a game.
 *
 * Always game-scoped, and the game is a required argument rather than a filter
 * — there is no "all playtests" query to call by mistake. Filters can only
 * narrow what a caller could already see.
 *
 * Resolution is unauthorized on purpose: finding the playtests and deciding
 * who may see them are separate steps, and every caller runs the policy
 * against the game first. Merging the two would make it easy to forget the
 * second half.
 *
 * @see PlaytestRepository::forGame()
 */
final class GetPlaytests
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    /**
     * @return Collection<int, Playtest>
     */
    public function handle(Game $game, ?PlaytestFilters $filters = null): Collection
    {
        return $this->playtests->forGame($game, $filters);
    }
}
