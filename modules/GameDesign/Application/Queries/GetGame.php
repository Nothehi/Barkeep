<?php

namespace Modules\GameDesign\Application\Queries;

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Resolve a game by its address within a workspace.
 *
 * The workspace is required because the address alone does not identify a
 * game: two workspaces may each have one at `bears-and-bridges`, and asking
 * for the address without saying whose is a question with no answer.
 *
 * That requirement is also what closes the obvious hole. There is no way to
 * ask this query for "the game with address X" and get somebody else's.
 */
final class GetGame
{
    public function __construct(private readonly GameRepository $games) {}

    public function handle(Workspace $workspace, GameSlug $slug): ?Game
    {
        return $this->games->findBySlug($workspace, $slug);
    }
}
