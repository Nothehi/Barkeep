<?php

namespace Modules\Playtesting\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\ValueObjects\GameGrant;
use Modules\Playtesting\Infrastructure\Authorization\GameAccess;

/**
 * The single place playtest access is decided.
 *
 * Every answer comes from two inputs: what the game the playtest belongs to
 * permits this account, and the playtest's own status. Nothing here reads a
 * workspace, a membership or a role — GameDesign has already turned all of
 * that into the grant this policy is written against, which is what keeps the
 * tenancy rules in one module rather than three.
 *
 * The game is always taken from the playtest rather than from the route, so
 * "may I edit this playtest?" is answered against the game it actually belongs
 * to. A playtest id from another game therefore fails here even if the URL
 * names a game the caller does have access to.
 *
 * Two kinds of "no" are returned, matching the convention Workspace and
 * GameDesign already follow:
 *
 * - somebody who cannot see the game gets a 404, so playtest ids cannot be
 *   used to discover what a studio is working on;
 * - somebody who can see it but may not act gets a 403, because they already
 *   know the playtest exists and hiding it would only confuse them.
 *
 * Reading and writing come apart on archived games, and that separation is the
 * historical integrity rule in practice. An archived game still answers `view`,
 * so a playtest run against it a year ago stays readable; it refuses `update`,
 * so nothing new can be attached to a project that has been put away.
 */
class PlaytestPolicy
{
    public function __construct(private readonly GameAccess $games) {}

    /**
     * See the playtests of a game.
     */
    public function viewAny(User $user, Game $game): Response
    {
        return $this->grant($user, $game)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Plan a new playtest against a game.
     *
     * Open to every member of the workspace, because that is what game write
     * access already means. Playtesting is the work; restricting who may
     * record it to the people who administer the studio would be a strange
     * shape for a design tool.
     */
    public function create(User $user, Game $game): Response
    {
        return $this->requireGameWriteAccess($user, $game);
    }

    /**
     * Read a playtest and everything scoped to it.
     */
    public function view(User $user, Playtest $playtest): Response
    {
        return $this->grantFor($user, $playtest)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Rewrite a playtest's plan.
     *
     * The plan is the question being asked, and a finished investigation's
     * question is not open to revision — see {@see recordConclusion()} for the
     * one field that stays writable afterwards.
     */
    public function update(User $user, Playtest $playtest): Response
    {
        return $this->requireOpenPlaytest($user, $playtest);
    }

    /**
     * Write down what a playtest concluded.
     *
     * Deliberately a separate ability from updating the plan, because it
     * outlives it. Conclusions are drawn after the sessions are over, often
     * days later, so a completed playtest allows this and nothing else.
     */
    public function recordConclusion(User $user, Playtest $playtest): Response
    {
        $decision = $this->requireGameWriteAccessFor($user, $playtest);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $playtest->acceptsAnalysis()
            ? Response::allow()
            : Response::deny($playtest->status->deniedReason());
    }

    /**
     * Close a playtest as answered.
     */
    public function complete(User $user, Playtest $playtest): Response
    {
        return $this->requireOpenPlaytest($user, $playtest);
    }

    /**
     * Call a playtest off.
     */
    public function cancel(User $user, Playtest $playtest): Response
    {
        return $this->requireOpenPlaytest($user, $playtest);
    }

    /**
     * See a playtest's sittings.
     */
    public function viewSessions(User $user, Playtest $playtest): Response
    {
        return $this->view($user, $playtest);
    }

    /**
     * Schedule another sitting of a playtest.
     */
    public function createSession(User $user, Playtest $playtest): Response
    {
        return $this->requireOpenPlaytest($user, $playtest);
    }

    /**
     * Destroy a playtest outright.
     *
     * No route reaches this today: playtests are cancelled rather than
     * deleted, so that the record of what was tried survives. The ability is
     * defined because the policy is the right place to have already decided
     * that nobody may — evidence that can be quietly removed is not evidence.
     */
    public function delete(User $user, Playtest $playtest): Response
    {
        return Response::deny(__('Playtests are cancelled rather than deleted.'));
    }

    /**
     * Require that the caller may change this particular playtest.
     *
     * Two gates, in order. The game has to be open to the caller and still
     * accepting changes; then the playtest itself has to be one that can still
     * change. A completed playtest is refused rather than hidden — the caller
     * can see it, so pretending it is gone would be a lie.
     */
    private function requireOpenPlaytest(User $user, Playtest $playtest): Response
    {
        $decision = $this->requireGameWriteAccessFor($user, $playtest);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $playtest->isModifiable()
            ? Response::allow()
            : Response::deny($playtest->status->deniedReason());
    }

    /**
     * Require write access to the game a playtest belongs to.
     */
    private function requireGameWriteAccessFor(User $user, Playtest $playtest): Response
    {
        $game = $playtest->game;

        return $game === null
            ? $this->hide()
            : $this->requireGameWriteAccess($user, $game);
    }

    /**
     * Require that the caller may record things against a game.
     */
    private function requireGameWriteAccess(User $user, Game $game): Response
    {
        $grant = $this->grant($user, $game);

        if (! $grant->allowsReading()) {
            return $this->hide();
        }

        return $grant->allowsWriting()
            ? Response::allow()
            : Response::deny($grant->deniedReason ?? __('This game is not accepting changes.'));
    }

    /**
     * Resolve the caller's standing in the game a playtest belongs to.
     */
    private function grantFor(User $user, Playtest $playtest): GameGrant
    {
        $game = $playtest->game;

        return $game === null
            ? GameGrant::none()
            : $this->grant($user, $game);
    }

    /**
     * Resolve the caller's standing in a game.
     */
    private function grant(User $user, Game $game): GameGrant
    {
        return $this->games->grantFor($user, $game);
    }

    /**
     * Deny in a way that does not admit the playtest exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Playtest not found.'));
    }
}
