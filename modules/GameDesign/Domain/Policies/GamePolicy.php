<?php

namespace Modules\GameDesign\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\ValueObjects\WorkspaceGrant;
use Modules\GameDesign\Infrastructure\Authorization\WorkspaceAccess;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The single place game access is decided.
 *
 * Every answer comes from three inputs: the acting account's standing in the
 * workspace the game belongs to, the workspace's own state, and the game's
 * status. No controller compares a workspace id itself, and no ability is
 * decided from an identifier that arrived in a request.
 *
 * The workspace is always taken from the game rather than from the route, so
 * "may I edit this game?" is answered against the boundary the game actually
 * lives in. A game id from another workspace therefore fails here even if the
 * URL names a workspace the caller does administer.
 *
 * Two kinds of "no" are returned, matching Workspace's own convention:
 *
 * - somebody outside the workspace gets a 404, so game addresses cannot be
 *   used to discover which projects a studio has;
 * - a member who lacks the standing gets a 403, because they already know the
 *   game exists and hiding it would only confuse them.
 *
 * Authorization today is workspace-shaped: every member of a workspace can
 * work on every game in it. Per-game roles are a plausible future, and the
 * shape here anticipates them — each ability already asks a question of its
 * own rather than sharing one "can this person edit?" branch, so a game-level
 * grant can be folded into {@see requireWriteAccess()} without any caller
 * changing.
 */
class GamePolicy
{
    public function __construct(private readonly WorkspaceAccess $workspaces) {}

    /**
     * See the games in a workspace.
     */
    public function viewAny(User $user, Workspace $workspace): Response
    {
        return $this->grant($user, $workspace)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Start a new game in a workspace.
     *
     * Open to every member. A workspace is a studio, and a studio where only
     * administrators may start a project is not one anybody would want.
     */
    public function create(User $user, Workspace $workspace): Response
    {
        return $this->requireWorkspaceWriteAccess($user, $workspace);
    }

    /**
     * Read a game and anything scoped to it.
     */
    public function view(User $user, Game $game): Response
    {
        return $this->grantForGame($user, $game)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Change a game's name, address or description.
     */
    public function update(User $user, Game $game): Response
    {
        return $this->requireWriteAccess($user, $game);
    }

    /**
     * Move a game through its project lifecycle.
     *
     * Which moves are legal is the domain's business, not the policy's; this
     * only answers whether the caller may make one at all.
     */
    public function changeStatus(User $user, Game $game): Response
    {
        return $this->requireWriteAccess($user, $game);
    }

    /**
     * Move a game through the design process.
     */
    public function changeDesignPhase(User $user, Game $game): Response
    {
        return $this->requireWriteAccess($user, $game);
    }

    /**
     * Put a game away.
     *
     * Restricted to the people who administer the workspace. Archiving ends a
     * project's editable life for everybody in the studio, and it cannot
     * currently be undone, so it does not belong to whoever happens to be
     * looking at the game.
     */
    public function archive(User $user, Game $game): Response
    {
        $decision = $this->requireWriteAccess($user, $game);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $this->grantForGame($user, $game)->canAdminister
            ? Response::allow()
            : Response::deny(__('Only a workspace admin can archive a game.'));
    }

    /**
     * Destroy a game outright.
     *
     * No route reaches this today: games are archived rather than deleted, so
     * that a studio's history survives. The ability is defined because the
     * policy is the right place to have already decided who could ever do it
     * — the workspace's owners and admins, and nobody else.
     */
    public function delete(User $user, Game $game): Response
    {
        return $this->archive($user, $game);
    }

    /**
     * See a game's iterations.
     */
    public function viewVersions(User $user, Game $game): Response
    {
        return $this->view($user, $game);
    }

    /**
     * Record a new iteration of a game.
     */
    public function createVersion(User $user, Game $game): Response
    {
        return $this->requireWriteAccess($user, $game);
    }

    /**
     * Require that the caller may change this particular game.
     *
     * Two gates, in order. The workspace has to be open to the caller and
     * still accepting changes; then the game itself has to be one that can
     * still change. An archived game is refused rather than hidden — the
     * caller can see it, so pretending it is gone would be a lie.
     */
    private function requireWriteAccess(User $user, Game $game): Response
    {
        $workspace = $game->workspace;

        if ($workspace === null) {
            return $this->hide();
        }

        $decision = $this->requireWorkspaceWriteAccess($user, $workspace);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $game->isModifiable()
            ? Response::allow()
            : Response::deny($game->status->deniedReason());
    }

    /**
     * Require that the caller may change things inside the workspace.
     */
    private function requireWorkspaceWriteAccess(User $user, Workspace $workspace): Response
    {
        $grant = $this->grant($user, $workspace);

        if (! $grant->allowsReading()) {
            return $this->hide();
        }

        return $grant->allowsWriting()
            ? Response::allow()
            : Response::deny($grant->deniedReason ?? __('This workspace is not accepting changes.'));
    }

    /**
     * Resolve the caller's standing in the workspace a game belongs to.
     */
    private function grantForGame(User $user, Game $game): WorkspaceGrant
    {
        $workspace = $game->workspace;

        return $workspace === null
            ? WorkspaceGrant::none()
            : $this->grant($user, $workspace);
    }

    /**
     * Resolve the caller's standing in a workspace.
     */
    private function grant(User $user, Workspace $workspace): WorkspaceGrant
    {
        return $this->workspaces->grantFor($user, $workspace);
    }

    /**
     * Deny in a way that does not admit the game exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Game not found.'));
    }
}
