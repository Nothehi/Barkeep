<?php

namespace Modules\GameDesign\Application\Queries;

use Modules\GameDesign\Application\DTOs\GameDashboard;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;

/**
 * Everything a game's home screen needs, gathered once.
 *
 * The overview asks three questions of the same game, and asking them
 * together here keeps the controller from making three separate trips and
 * the screen from deciding what an overview consists of.
 *
 * What it does not gather is as deliberate as what it does. There are no
 * playtest counts, no feedback summaries and no progress metrics, because
 * nothing in the platform can answer those yet and a dashboard that displays
 * invented numbers is worse than one that displays fewer real ones.
 */
final class GetGameDashboard
{
    public function __construct(private readonly GameRepository $games) {}

    public function handle(Game $game): GameDashboard
    {
        return new GameDashboard(
            game: $game,
            versionCount: $this->games->countVersionsOf($game),
            latestVersion: $this->games->latestVersionOf($game),
        );
    }
}
