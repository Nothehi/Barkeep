<?php

namespace Modules\Playtesting\Application\Queries;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * One of a game's observations, by id, wherever in the game it was made.
 *
 * Playtesting's published answer to "does this observation belong to this
 * project?", and it exists so that another context can resolve a citation of one
 * without learning how playtesting stores it. PrototypeIteration's design
 * decisions may cite evidence; the citation arrives as a bare id in a request
 * body, and this is what makes it resolvable by the module that owns it.
 *
 * Takes a resolved game rather than a game id, so the workspace scoping that
 * produced it has already happened and cannot be skipped here. An observation id
 * from another studio's project returns null — indistinguishable from an id that
 * names nothing, which is what stops a citation from being used to discover that
 * somebody else's evidence exists.
 *
 * Unauthorized on purpose, like every query in this module: finding the record and
 * deciding who may see it are separate steps, and the caller runs a policy against
 * the game first.
 *
 * @see PlaytestRepository::findObservationInGame()
 */
final class GetObservationInGame
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    public function handle(Game $game, string $observationId): ?PlaytestObservation
    {
        return $this->playtests->findObservationInGame($game, $observationId);
    }
}
