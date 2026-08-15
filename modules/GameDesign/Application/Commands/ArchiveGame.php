<?php

namespace Modules\GameDesign\Application\Commands;

use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * Put a game away.
 *
 * Nothing is deleted. The game and every version of it stay readable — a
 * studio's abandoned prototypes are part of its history, and half of them get
 * mined for parts years later.
 *
 * Archival is a lifecycle move like any other, so it runs through
 * {@see ChangeGameStatus} rather than writing the column itself. That is what
 * keeps one transition matrix, one row lock and one set of events, and stops
 * "archive" from becoming the back door that bypasses all three.
 *
 * It exists as its own use case because it reads as one: the interface offers
 * "Archive game", not "set status to archived", and there is no route on
 * which a caller has to know that those are the same thing.
 */
final class ArchiveGame
{
    public function __construct(private readonly ChangeGameStatus $changeStatus) {}

    public function handle(User $actor, Game $game): Game
    {
        return $this->changeStatus->handle($actor, $game, GameStatus::Archived);
    }
}
