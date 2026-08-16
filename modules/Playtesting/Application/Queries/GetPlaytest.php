<?php

namespace Modules\Playtesting\Application\Queries;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * One of a game's playtests, by id.
 *
 * Takes a resolved game rather than a game id, so the workspace scoping that
 * produced it has already happened and cannot be skipped here. A playtest id
 * from a different game returns null and the route binding turns that into a
 * 404 — before any handler or policy runs.
 */
final class GetPlaytest
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    public function handle(Game $game, string $playtestId): ?Playtest
    {
        return $this->playtests->findForGame($game, $playtestId);
    }
}
