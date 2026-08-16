<?php

namespace Modules\GameDesign\Application\Commands;

use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Events\GameDesignPhaseChanged;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * Record where a game has got to in the design process.
 *
 * Unlike a status change, any phase may follow any other. That is not an
 * oversight — designing a board game is not a pipeline. A game in playtesting
 * that turns out to have a broken core loop goes back to prototyping, and a
 * rule that forbade it would describe a process nobody actually follows.
 *
 * There is no row lock here for the same reason: the phases have no matrix to
 * violate, so two people setting different phases at once produces a last
 * writer, not a corrupt state. The events record both moves either way.
 */
final class ChangeDesignPhase
{
    public function __construct(private readonly GameModificationGuard $guard) {}

    public function handle(User $actor, Game $game, DesignPhase $target): Game
    {
        $this->guard->ensureGameIsModifiable($game);

        $from = $game->design_phase;

        if ($from === $target) {
            return $game;
        }

        $game->forceFill(['design_phase' => $target])->save();

        event(new GameDesignPhaseChanged(
            gameId: $game->id,
            workspaceId: $game->workspace_id,
            changedBy: $actor->id,
            from: $from,
            to: $target,
        ));

        return $game;
    }
}
