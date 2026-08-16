<?php

namespace Modules\Playtesting\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Domain\ValueObjects\GameGrant;
use Modules\Playtesting\Infrastructure\Authorization\GameAccess;

/**
 * The single place session access is decided.
 *
 * Answers three questions in order, and all three have to say yes: what the
 * game permits this account, whether the playtest is still open, and whether
 * the session itself still is. The chain is walked from the session upwards —
 * session, playtest, game — so authorization is always decided against the
 * game the session actually belongs to rather than one named in a URL.
 *
 * That last point is the whole security property of the module. A session id
 * from somebody else's workspace resolves to a session whose game the caller
 * cannot see, which is a 404 here regardless of what the rest of the address
 * says.
 *
 * Evidence gets its own abilities rather than sharing "update". Adding an
 * observation to a running session and renaming that session are different
 * acts with different rules — the first is the whole point of a live session
 * and the second is housekeeping — and giving them one ability would have
 * meant one of them getting the wrong answer.
 */
class PlaytestSessionPolicy
{
    public function __construct(private readonly GameAccess $games) {}

    /**
     * Read a session and everything recorded in it.
     */
    public function view(User $user, PlaytestSession $session): Response
    {
        return $this->grantFor($user, $session)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Change a session's own details: where it is, the notes, the outcome.
     */
    public function update(User $user, PlaytestSession $session): Response
    {
        return $this->requireOpenSession($user, $session);
    }

    /**
     * Begin the session.
     */
    public function start(User $user, PlaytestSession $session): Response
    {
        return $this->requireOpenSession($user, $session);
    }

    /**
     * End the session.
     */
    public function complete(User $user, PlaytestSession $session): Response
    {
        return $this->requireOpenSession($user, $session);
    }

    /**
     * Call the session off.
     */
    public function cancel(User $user, PlaytestSession $session): Response
    {
        return $this->requireOpenSession($user, $session);
    }

    /**
     * Add or remove the people at the table.
     */
    public function manageParticipants(User $user, PlaytestSession $session): Response
    {
        return $this->requireSessionAcceptsEvidence($user, $session);
    }

    /**
     * Record something noticed during the session.
     */
    public function createObservation(User $user, PlaytestSession $session): Response
    {
        return $this->requireSessionAcceptsEvidence($user, $session);
    }

    /**
     * Correct or withdraw an observation.
     *
     * The same answer as recording one. An observation is a note somebody
     * typed at a table, not a signed statement, and the session closing is
     * what fixes it — not who happened to type it.
     */
    public function manageObservations(User $user, PlaytestSession $session): Response
    {
        return $this->requireSessionAcceptsEvidence($user, $session);
    }

    /**
     * Record what a participant said.
     */
    public function createFeedback(User $user, PlaytestSession $session): Response
    {
        return $this->requireSessionAcceptsEvidence($user, $session);
    }

    /**
     * Correct or withdraw a piece of feedback.
     */
    public function manageFeedback(User $user, PlaytestSession $session): Response
    {
        return $this->requireSessionAcceptsEvidence($user, $session);
    }

    /**
     * Destroy a session outright.
     *
     * No route reaches this: sessions are cancelled rather than deleted, for
     * the same reason playtests are. What was tried is part of the record.
     */
    public function delete(User $user, PlaytestSession $session): Response
    {
        return Response::deny(__('Sessions are cancelled rather than deleted.'));
    }

    /**
     * Require that the session may still be moved through its lifecycle.
     */
    private function requireOpenSession(User $user, PlaytestSession $session): Response
    {
        $decision = $this->requireWriteAccess($user, $session);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $session->isModifiable()
            ? Response::allow()
            : Response::deny($session->status->deniedReason());
    }

    /**
     * Require that the session may still gain participants and evidence.
     *
     * The same question as above today, and kept separate anyway: "may this
     * session be renamed?" and "may something be added to it?" are different
     * questions that happen to share an answer, and the day they stop sharing
     * one there should be two places to change rather than one to split.
     */
    private function requireSessionAcceptsEvidence(User $user, PlaytestSession $session): Response
    {
        $decision = $this->requireWriteAccess($user, $session);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $session->acceptsEvidence()
            ? Response::allow()
            : Response::deny($session->status->deniedReason());
    }

    /**
     * Require write access to the game, and an open playtest above it.
     *
     * The playtest is checked before the session because saying "this playtest
     * is over" is more useful than complaining about one of its sittings.
     */
    private function requireWriteAccess(User $user, PlaytestSession $session): Response
    {
        $playtest = $session->playtest;

        if ($playtest === null) {
            return $this->hide();
        }

        $game = $playtest->game;

        if ($game === null) {
            return $this->hide();
        }

        $grant = $this->grant($user, $game);

        if (! $grant->allowsReading()) {
            return $this->hide();
        }

        if (! $grant->allowsWriting()) {
            return Response::deny($grant->deniedReason ?? __('This game is not accepting changes.'));
        }

        return $playtest->isModifiable()
            ? Response::allow()
            : Response::deny($playtest->status->deniedReason());
    }

    /**
     * Resolve the caller's standing in the game a session belongs to.
     */
    private function grantFor(User $user, PlaytestSession $session): GameGrant
    {
        $playtest = $session->playtest;

        if (! $playtest instanceof Playtest) {
            return GameGrant::none();
        }

        $game = $playtest->game;

        return $game instanceof Game ? $this->grant($user, $game) : GameGrant::none();
    }

    /**
     * Resolve the caller's standing in a game.
     */
    private function grant(User $user, Game $game): GameGrant
    {
        return $this->games->grantFor($user, $game);
    }

    /**
     * Deny in a way that does not admit the session exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Session not found.'));
    }
}
