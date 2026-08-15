<?php

namespace Modules\GameDesign\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\GameDesign\Application\DTOs\UpdateGameData;
use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Events\GameUpdated;
use Modules\GameDesign\Domain\Exceptions\InvalidGameSlug;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * Change a game's own metadata.
 *
 * Only the game's identity is touched here — its name, address and
 * description. Status and design phase each have their own use case, because
 * each has its own rules about what a legal move is, and folding them in
 * would make those rules optional.
 */
final class UpdateGame
{
    public function __construct(private readonly GameModificationGuard $guard) {}

    public function handle(User $actor, Game $game, UpdateGameData $data): Game
    {
        $this->guard->ensureGameIsModifiable($game);

        $game->fill([
            'name' => $data->name,
            'slug' => $data->slug->value,
            'description' => $data->description,
        ]);

        $changed = array_keys($game->getDirty());

        if ($changed === []) {
            return $game;
        }

        try {
            $game->save();
        } catch (UniqueConstraintViolationException) {
            throw InvalidGameSlug::taken($data->slug->value);
        }

        event(new GameUpdated(
            gameId: $game->id,
            workspaceId: $game->workspace_id,
            updatedBy: $actor->id,
            changed: $changed,
        ));

        return $game;
    }
}
