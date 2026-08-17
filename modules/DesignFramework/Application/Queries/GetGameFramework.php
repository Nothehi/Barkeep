<?php

namespace Modules\DesignFramework\Application\Queries;

use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;
use Modules\GameDesign\Domain\Models\Game;

/**
 * The methodology a game follows, if it follows one.
 *
 * Always found through the game, never by id, so there is no lookup that could return
 * another studio's adoption. The game itself was resolved through a workspace by
 * GameDesign's own binding, so the whole ownership chain holds by construction rather than
 * by each caller remembering to check it.
 *
 * Null is an ordinary answer rather than an error: most games do not follow a framework,
 * and the screen that asks this is the one offering to adopt one.
 */
final class GetGameFramework
{
    public function __construct(private readonly GameFrameworkRepository $adoptions) {}

    public function handle(Game $game): ?GameFramework
    {
        return $this->adoptions->forGame($game);
    }
}
