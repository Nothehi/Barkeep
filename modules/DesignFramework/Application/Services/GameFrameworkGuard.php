<?php

namespace Modules\DesignFramework\Application\Services;

use Modules\DesignFramework\Domain\Exceptions\GameFrameworkIsNotAcceptingProgress;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Models\Game;

/**
 * The one place "may this game record framework work?" is answered.
 *
 * Three things can close the door, and every write has to clear all of them: the
 * workspace may have stopped accepting changes, the game may have been archived,
 * and the adoption may be paused or complete. The first two are not this module's
 * business, so they are delegated to GameDesign's own guard rather than
 * reimplemented — which is what stops "an archived game is read-only" from having a
 * second definition here that drifts from the first.
 *
 * The policies check all of this before a request reaches a command, but the policy
 * only guards the HTTP door. Every command runs this too, so a caller arriving
 * another way — a console command, a queued job, a later module — cannot write
 * against a game the product considers closed.
 */
final class GameFrameworkGuard
{
    public function __construct(private readonly GameModificationGuard $games) {}

    /**
     * Require that a game may take up a framework.
     *
     * @throws GameIsNotModifiable
     */
    public function ensureGameAcceptsFramework(Game $game): void
    {
        $this->games->ensureGameIsModifiable($game);
    }

    /**
     * Require that a game may record an evaluation, completion, tick or answer.
     *
     * The game is checked first: if the project is closed, saying so is more useful
     * than complaining about the adoption inside it.
     *
     * @throws GameFrameworkIsNotAcceptingProgress
     */
    public function ensureAdoptionAcceptsProgress(GameFramework $adoption): void
    {
        $this->ensureGameIsOpen($adoption);

        if (! $adoption->acceptsProgress()) {
            throw GameFrameworkIsNotAcceptingProgress::forStatus($adoption->status);
        }
    }

    /**
     * Require that a game may still move its adoption through its lifecycle.
     *
     * Looser than the check above by exactly one status, and that is why the two
     * exist separately: a paused adoption refuses new work and accepts being
     * resumed. Without the distinction, pausing would be a one-way door.
     *
     * @throws GameFrameworkIsNotAcceptingProgress
     */
    public function ensureAdoptionIsOpen(GameFramework $adoption): void
    {
        $this->ensureGameIsOpen($adoption);

        if ($adoption->isComplete()) {
            throw GameFrameworkIsNotAcceptingProgress::forStatus($adoption->status);
        }
    }

    /**
     * Require that the game an adoption belongs to is still open.
     *
     * Reported as a framework problem even though the game is what refused, because
     * the caller was acting on the framework and that is the screen they are looking
     * at. The message comes from GameDesign, so they are still told the real reason.
     */
    private function ensureGameIsOpen(GameFramework $adoption): void
    {
        $game = $adoption->game;

        if ($game === null) {
            return;
        }

        try {
            $this->games->ensureGameIsModifiable($game);
        } catch (GameIsNotModifiable $refusal) {
            throw GameFrameworkIsNotAcceptingProgress::becauseGameIsClosed($refusal->getMessage());
        }
    }
}
