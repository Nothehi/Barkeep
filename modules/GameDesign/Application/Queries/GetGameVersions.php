<?php

namespace Modules\GameDesign\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;

/**
 * A game's iterations, newest first, with the accounts that cut them.
 *
 * Takes a resolved game rather than an id, so the workspace scoping that
 * produced it has already happened and cannot be skipped here.
 */
final class GetGameVersions
{
    public function __construct(private readonly GameRepository $games) {}

    /**
     * @return Collection<int, GameVersion>
     */
    public function handle(Game $game): Collection
    {
        return $this->games->versionsOf($game);
    }
}
