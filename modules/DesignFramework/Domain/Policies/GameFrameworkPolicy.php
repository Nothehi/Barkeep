<?php

namespace Modules\DesignFramework\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\ValueObjects\GameGrant;
use Modules\DesignFramework\Infrastructure\Authorization\GameAccess;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * The single place a game's framework work is authorized.
 *
 * Two policies in this module, because there are genuinely two questions. Writing
 * a methodology is a platform-wide privilege ({@see FrameworkPolicy}); working
 * one is ordinary studio work, scoped to a game the way everything else in
 * Barkeep is.
 *
 * Every answer here comes from two inputs: what the game permits this account, and
 * the adoption's own status. Nothing reads a workspace, a membership or a role —
 * GameDesign has already turned all of that into the grant this policy is written
 * against, which is what keeps the tenancy rules in one module rather than four.
 *
 * The game is always taken from the adoption rather than from the route, so "may I
 * evaluate this criterion?" is answered against the game the adoption actually
 * belongs to. An adoption id from another studio therefore fails here even if the
 * URL names a game the caller does have access to.
 *
 * Two kinds of "no", matching the convention the contexts below follow:
 *
 * - somebody who cannot see the game gets a 404, so framework routes cannot be
 *   used to discover what a studio is working on;
 * - somebody who can see it but may not act gets a 403.
 *
 * Reading and writing come apart on archived games, and that separation is what
 * keeps a shelved design's assessment legible: an archived game still answers
 * `view`, so the framework work done on it a year ago stays readable, and refuses
 * `recordProgress`, so nothing new can be attached to a project that has been put
 * away.
 *
 * Nothing here decides whether a *criterion* belongs to the version the game
 * adopted. That is not a permission — it is resolution, and it happens in
 * `FrameworkContentLocator`, which looks content up through the adoption so a
 * mismatched id is never found rather than being found and refused.
 */
class GameFrameworkPolicy
{
    public function __construct(private readonly GameAccess $games) {}

    /**
     * See whether a game follows a framework, and what it has recorded.
     *
     * Asked against the game rather than against an adoption, because the screen
     * needs an answer before there is an adoption to ask about — a game with no
     * framework still has a page offering to adopt one.
     */
    public function viewForGame(User $user, Game $game): Response
    {
        return $this->grant($user, $game)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Adopt a framework version for a game.
     *
     * Open to every member of the workspace, because that is what game write
     * access already means. Choosing a methodology is design work; restricting it
     * to the people who administer the studio would be a strange shape for a
     * design tool.
     */
    public function assign(User $user, Game $game): Response
    {
        return $this->requireGameWriteAccess($user, $game);
    }

    /**
     * Read an adoption and everything recorded against it.
     */
    public function view(User $user, GameFramework $adoption): Response
    {
        return $this->grantFor($user, $adoption)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Record framework work: an evaluation, a completion, a tick, an answer.
     *
     * One ability for all four, because the rule they share is the whole rule —
     * the game must be open and the adoption must be active. Splitting it into
     * four would be four chances for one of them to forget the second half.
     */
    public function recordProgress(User $user, GameFramework $adoption): Response
    {
        $decision = $this->requireGameWriteAccessFor($user, $adoption);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $adoption->acceptsProgress()
            ? Response::allow()
            : Response::deny($adoption->status->deniedReason());
    }

    /**
     * Step away from a framework for a while.
     */
    public function pause(User $user, GameFramework $adoption): Response
    {
        return $this->requireOpenAdoption($user, $adoption);
    }

    /**
     * Pick a paused framework back up.
     */
    public function resume(User $user, GameFramework $adoption): Response
    {
        return $this->requireOpenAdoption($user, $adoption);
    }

    /**
     * Declare the game finished with its framework.
     */
    public function complete(User $user, GameFramework $adoption): Response
    {
        return $this->requireOpenAdoption($user, $adoption);
    }

    /**
     * Abandon a framework outright.
     *
     * No route reaches this. An adoption is paused or completed rather than
     * deleted, because deleting it would take every evaluation, completion and
     * written answer with it — months of a studio's design thinking, removed by a
     * button. The ability is defined because the policy is the right place to have
     * already decided that nobody may.
     */
    public function delete(User $user, GameFramework $adoption): Response
    {
        return Response::deny(__('A framework is paused or completed rather than abandoned.'));
    }

    /**
     * Require that the caller may move this adoption through its lifecycle.
     */
    private function requireOpenAdoption(User $user, GameFramework $adoption): Response
    {
        $decision = $this->requireGameWriteAccessFor($user, $adoption);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $adoption->isComplete()
            ? Response::deny($adoption->status->deniedReason())
            : Response::allow();
    }

    /**
     * Require write access to the game an adoption belongs to.
     */
    private function requireGameWriteAccessFor(User $user, GameFramework $adoption): Response
    {
        $game = $adoption->game;

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
     * Resolve the caller's standing in the game an adoption belongs to.
     */
    private function grantFor(User $user, GameFramework $adoption): GameGrant
    {
        $game = $adoption->game;

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
     * Deny in a way that does not admit the adoption exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Framework not found.'));
    }
}
