<?php

namespace Modules\GameDesign\Application\Queries;

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Domain\ValueObjects\VersionNumber;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;

/**
 * One iteration of a game, by its number.
 *
 * Scoped to the game the same way a game is scoped to its workspace: a number
 * only means something inside one game, so there is no way to ask for "v3"
 * and receive somebody else's.
 */
final class GetGameVersion
{
    public function __construct(private readonly GameRepository $games) {}

    public function handle(Game $game, VersionNumber $number): ?GameVersion
    {
        return $this->games->findVersion($game, $number);
    }
}
