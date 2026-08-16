<?php

namespace Modules\GameDesign\Application\Services;

use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The one place "may this still change?" is answered.
 *
 * An archived game is read-only, and so is every game inside a workspace that
 * has stopped accepting changes. Both rules are checked by the policy before
 * a request reaches a command — but the policy only guards the HTTP door.
 * Every command runs this too, so a caller that arrives another way (a
 * console command, a queued job, a later module) cannot write to a game the
 * product considers closed.
 *
 * Having exactly one implementation is the point. "Archived games cannot be
 * modified" spread across five commands is five chances to forget it in the
 * sixth.
 */
final class GameModificationGuard
{
    /**
     * Require that the given game may still be changed.
     *
     * The workspace is checked first: if the boundary is closed, saying so is
     * more useful than complaining about the game inside it.
     *
     * @throws GameIsNotModifiable
     */
    public function ensureGameIsModifiable(Game $game): void
    {
        $workspace = $game->workspace;

        if ($workspace !== null) {
            $this->ensureWorkspaceIsModifiable($workspace);
        }

        if (! $game->isModifiable()) {
            throw GameIsNotModifiable::forStatus($game->status);
        }
    }

    /**
     * Require that the given workspace is still accepting changes.
     *
     * @throws GameIsNotModifiable
     */
    public function ensureWorkspaceIsModifiable(Workspace $workspace): void
    {
        if ($workspace->isModifiable()) {
            return;
        }

        throw GameIsNotModifiable::becauseWorkspaceIsClosed(
            $workspace->status->deniedReason(),
        );
    }
}
