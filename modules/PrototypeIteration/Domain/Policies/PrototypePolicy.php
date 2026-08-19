<?php

namespace Modules\PrototypeIteration\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\ValueObjects\GameGrant;
use Modules\PrototypeIteration\Infrastructure\Authorization\GameAccess;

/**
 * The single place prototype access is decided.
 *
 * Every answer comes from two inputs: what the game the prototype belongs to permits this
 * account, and the prototype's own status. Nothing here reads a workspace, a membership or a
 * role — GameDesign has already turned all of that into the grant this policy is written
 * against, which is what keeps the tenancy rules in one module rather than three.
 *
 * The game is always taken from the prototype rather than from the route, so "may I edit this
 * prototype?" is answered against the game it actually belongs to. A prototype id from
 * another game therefore fails here even if the URL names a game the caller does have access
 * to.
 *
 * Two kinds of "no" are returned, matching the convention Workspace, GameDesign and
 * Playtesting already follow:
 *
 * - somebody who cannot see the game gets a 404, so prototype ids cannot be used to discover
 *   what a studio is working on;
 * - somebody who can see it but may not act gets a 403, because they already know the
 *   prototype exists and hiding it would only confuse them.
 *
 * Reading and writing come apart on archived games and archived prototypes alike, and that
 * separation is the historical integrity rule in practice. An archived game still answers
 * `view`, so a prototype built against it two years ago stays readable along with every
 * iteration run on it; it refuses `update`, so nothing new can be attached to work that has
 * been put away.
 */
class PrototypePolicy
{
    public function __construct(private readonly GameAccess $games) {}

    /**
     * See the prototypes of a game.
     */
    public function viewAny(User $user, Game $game): Response
    {
        return $this->grant($user, $game)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Start a new prototype for a game.
     *
     * Open to every member of the workspace, because that is what game write access already
     * means. Building prototypes is the work; restricting who may record it to the people who
     * administer the studio would be a strange shape for a design tool.
     */
    public function create(User $user, Game $game): Response
    {
        return $this->requireGameWriteAccess($user, $game);
    }

    /**
     * Read a prototype and everything scoped to it.
     */
    public function view(User $user, Prototype $prototype): Response
    {
        return $this->grantFor($user, $prototype)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Change a prototype's own details.
     */
    public function update(User $user, Prototype $prototype): Response
    {
        return $this->requireOpenPrototype($user, $prototype);
    }

    /**
     * Put a prototype away for good.
     *
     * The same requirement as updating it, and deliberately not a stronger one. Archiving is
     * irreversible, which argues for care — but it is also the ordinary end of a prototype's
     * life, done by whoever was building it, and a permission that made a designer ask
     * somebody else to tidy up after them would simply mean nothing ever got archived.
     */
    public function archive(User $user, Prototype $prototype): Response
    {
        return $this->requireOpenPrototype($user, $prototype);
    }

    /**
     * Cut a new state of a prototype.
     *
     * Gated on exactly the same thing as editing, which is the load-bearing part of the whole
     * immutability arrangement. The module refuses edits to a version anything has been built
     * on, and that refusal is only reasonable if the way forward is always open: anybody who
     * may touch the prototype may cut the next version of it.
     */
    public function createVersion(User $user, Prototype $prototype): Response
    {
        return $this->requireOpenPrototype($user, $prototype);
    }

    /**
     * See a prototype's states and their files.
     */
    public function viewVersions(User $user, Prototype $prototype): Response
    {
        return $this->view($user, $prototype);
    }

    /**
     * Attach a file to one of a prototype's states.
     *
     * Allowed on a version that has already been built upon, which is the one place the
     * immutability rule deliberately does not reach. A print sheet uploaded later documents
     * what v3 was; it does not change it, and somebody finding last month's card fronts on
     * their desktop should be able to file them where they belong.
     */
    public function createArtifact(User $user, Prototype $prototype): Response
    {
        return $this->requireOpenPrototype($user, $prototype);
    }

    /**
     * Remove a file from one of a prototype's states.
     *
     * The only destructive ability in the module, and it is permitted because an artifact is
     * documentation rather than reasoning: the wrong PDF or a duplicate is a mistake to correct,
     * with no design argument attached that deleting would rewrite.
     */
    public function deleteArtifact(User $user, Prototype $prototype): Response
    {
        return $this->requireOpenPrototype($user, $prototype);
    }

    /**
     * Destroy a prototype outright.
     *
     * No route reaches this. Prototypes are archived rather than deleted, so that the versions
     * a design history points at survive — and the ability is defined here because the policy
     * is the right place to have already decided that nobody may. An iteration whose prototype
     * version could vanish is an iteration that cannot say what was on the table.
     */
    public function delete(User $user, Prototype $prototype): Response
    {
        return Response::deny(__('Prototypes are archived rather than deleted.'));
    }

    /**
     * Require that the caller may change this particular prototype.
     *
     * Two gates, in order. The game has to be open to the caller and still accepting changes;
     * then the prototype itself has to be one that can still change. An archived prototype is
     * refused rather than hidden — the caller can see it, so pretending it is gone would be a
     * lie.
     */
    private function requireOpenPrototype(User $user, Prototype $prototype): Response
    {
        $decision = $this->requireGameWriteAccessFor($user, $prototype);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $prototype->isModifiable()
            ? Response::allow()
            : Response::deny($prototype->status->deniedReason());
    }

    /**
     * Require write access to the game a prototype belongs to.
     */
    private function requireGameWriteAccessFor(User $user, Prototype $prototype): Response
    {
        $game = $prototype->game;

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
     * Resolve the caller's standing in the game a prototype belongs to.
     */
    private function grantFor(User $user, Prototype $prototype): GameGrant
    {
        $game = $prototype->game;

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
     * Deny in a way that does not admit the prototype exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Prototype not found.'));
    }
}
