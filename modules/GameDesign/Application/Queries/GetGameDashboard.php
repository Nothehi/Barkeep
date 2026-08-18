<?php

namespace Modules\GameDesign\Application\Queries;

use Modules\GameDesign\Application\DTOs\GameDashboard;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;

/**
 * Everything a game's home screen needs, gathered once.
 *
 * The overview asks a handful of questions of the same game, and asking them
 * together here keeps the controller from making separate trips and the
 * screen from deciding what an overview consists of.
 *
 * The design record is gathered with the rest because it is the one substantial
 * thing the platform already knows about a game: a pitch, an audience, the
 * constraints, the mechanics and the core loop. It was being written down in
 * settings and read back nowhere.
 *
 * What it still does not gather is as deliberate as what it does. There are no
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
            designRecord: $this->games->designRecordOf($game),
        );
    }
}
