<?php

namespace Modules\Playtesting\Application\Services;

use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Domain\Exceptions\PlaytestIsNotModifiable;
use Modules\Playtesting\Domain\Exceptions\SessionIsNotModifiable;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * The one place "may this still change?" is answered.
 *
 * Three things can freeze a playtest, and every write has to clear all of
 * them: the workspace may have stopped accepting changes, the game may have
 * been archived, and the playtest or session may be over. The first two are
 * not this module's business, so they are delegated to GameDesign's own guard
 * rather than reimplemented — which is what stops "an archived game is
 * read-only" from having a second definition here that drifts from the first.
 *
 * The policies check all of this before a request reaches a command, but the
 * policy only guards the HTTP door. Every command runs this too, so a caller
 * that arrives another way — a console command, a queued job, a later module —
 * cannot write to a playtest the product considers closed.
 *
 * Having exactly one implementation is the point. "Completed sessions are
 * read-only" spread across six commands is six chances to forget it in the
 * seventh.
 */
final class PlaytestModificationGuard
{
    public function __construct(private readonly GameModificationGuard $games) {}

    /**
     * Require that the given game may still have playtests recorded against it.
     *
     * @throws GameIsNotModifiable
     */
    public function ensureGameAcceptsPlaytests(Game $game): void
    {
        $this->games->ensureGameIsModifiable($game);
    }

    /**
     * Require that the given playtest's plan may still be rewritten.
     *
     * The game is checked first: if the project is closed, saying so is more
     * useful than complaining about the playtest inside it.
     *
     * @throws PlaytestIsNotModifiable
     */
    public function ensurePlaytestIsModifiable(Playtest $playtest): void
    {
        $this->ensureGameIsOpen($playtest);

        if (! $playtest->isModifiable()) {
            throw PlaytestIsNotModifiable::forStatus($playtest->status);
        }
    }

    /**
     * Require that what the playtest concluded may still be written down.
     *
     * Looser than the check above by exactly one status, and that is the whole
     * reason the two exist separately: a completed playtest refuses everything
     * about its plan and accepts its conclusion.
     *
     * @throws PlaytestIsNotModifiable
     */
    public function ensurePlaytestAcceptsAnalysis(Playtest $playtest): void
    {
        $this->ensureGameIsOpen($playtest);

        if (! $playtest->acceptsAnalysis()) {
            throw PlaytestIsNotModifiable::forStatus($playtest->status);
        }
    }

    /**
     * Require that the given session may still be changed.
     *
     * @throws SessionIsNotModifiable
     */
    public function ensureSessionIsModifiable(PlaytestSession $session): void
    {
        $this->ensurePlaytestIsOpen($session);

        if (! $session->isModifiable()) {
            throw SessionIsNotModifiable::forStatus($session->status);
        }
    }

    /**
     * Require that the given session may still gain participants or evidence.
     *
     * @throws SessionIsNotModifiable
     */
    public function ensureSessionAcceptsEvidence(PlaytestSession $session): void
    {
        $this->ensurePlaytestIsOpen($session);

        if (! $session->acceptsEvidence()) {
            throw SessionIsNotModifiable::forStatus($session->status);
        }
    }

    /**
     * Require that the game a playtest belongs to is still open.
     */
    private function ensureGameIsOpen(Playtest $playtest): void
    {
        $game = $playtest->game;

        if ($game !== null) {
            $this->games->ensureGameIsModifiable($game);
        }
    }

    /**
     * Require that the playtest a session belongs to is still open.
     *
     * Reported as a session problem even though the playtest is what refused,
     * because the caller was acting on a session and that is the object they
     * will be looking at. The message comes from the playtest, so they are
     * still told the real reason.
     */
    private function ensurePlaytestIsOpen(PlaytestSession $session): void
    {
        $playtest = $session->playtest;

        if ($playtest === null) {
            return;
        }

        $this->ensureGameIsOpen($playtest);

        if (! $playtest->isModifiable()) {
            throw SessionIsNotModifiable::becausePlaytestIsClosed($playtest->status->deniedReason());
        }
    }
}
