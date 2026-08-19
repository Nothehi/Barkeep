<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * One of a game's prototypes, by id.
 *
 * Takes a resolved game rather than a game id, so the workspace scoping that produced it
 * has already happened and cannot be skipped here. A prototype id from a different game
 * returns null and the route binding turns that into a 404 — before any handler or policy
 * runs.
 */
final class GetPrototype
{
    public function __construct(private readonly PrototypeRepository $prototypes) {}

    public function handle(Game $game, string $prototypeId): ?Prototype
    {
        return $this->prototypes->findForGame($game, $prototypeId);
    }
}
