<?php

namespace Modules\PrototypeIteration\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\ValueObjects\GameGrant;
use Modules\PrototypeIteration\Infrastructure\Authorization\GameAccess;

/**
 * The single place iteration access is decided.
 *
 * The busiest policy in the platform, because the iteration screen offers more than any other:
 * changes, experiments, decisions, playtest attachments, three lifecycle moves and the optional
 * act of cutting a new game version. Every one of those is answered here, from two inputs —
 * what the game permits this account, and the iteration's own status.
 *
 * Nothing here reads a workspace, a membership or a role. GameDesign has already turned all of
 * that into the grant this policy is written against, which keeps the tenancy rules in one
 * module rather than four.
 *
 * The game is always taken from the iteration rather than from the route, so "may I record a
 * change on this cycle?" is answered against the game the cycle actually belongs to. An
 * iteration id from another game fails here even if the URL names a game the caller does have
 * access to.
 *
 * ## Why `recordWork` exists
 *
 * Almost every write inside a cycle — a change, an experiment, a decision, a citation, a
 * playtest link — asks the same question: is this cycle still open to design work? Giving that
 * one name, checked in one place, is what stops eight separate abilities from drifting apart as
 * the rules change. The individual commands still each run the guard, because a policy only
 * guards the HTTP door.
 *
 * ## Read and write come apart, permanently
 *
 * A completed iteration answers `view` forever and refuses every write. That is not a
 * convenience; it is the module's deliverable. The design history has to stay legible after
 * the project is archived, after the prototype is archived, and after everybody who worked on
 * it has moved on — and it has to stop being editable the moment it becomes history.
 */
class IterationPolicy
{
    public function __construct(private readonly GameAccess $games) {}

    /**
     * See the design cycles of a game.
     */
    public function viewAny(User $user, Game $game): Response
    {
        return $this->grant($user, $game)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Plan a new cycle against a game.
     */
    public function create(User $user, Game $game): Response
    {
        return $this->requireGameWriteAccess($user, $game);
    }

    /**
     * Read a cycle and everything scoped to it.
     */
    public function view(User $user, Iteration $iteration): Response
    {
        return $this->grantFor($user, $iteration)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Rewrite a cycle's plan.
     *
     * The plan is the question the cycle set out to answer, and a finished cycle's question is
     * not open to revision — changing it would leave a record whose conclusions no longer
     * answer what it claims to have asked.
     */
    public function update(User $user, Iteration $iteration): Response
    {
        return $this->requireOpenIteration($user, $iteration);
    }

    /**
     * Begin the work on a planned cycle.
     */
    public function start(User $user, Iteration $iteration): Response
    {
        return $this->requireOpenIteration($user, $iteration);
    }

    /**
     * Close a cycle with an outcome.
     */
    public function complete(User $user, Iteration $iteration): Response
    {
        return $this->requireOpenIteration($user, $iteration);
    }

    /**
     * Call a cycle off.
     */
    public function cancel(User $user, Iteration $iteration): Response
    {
        return $this->requireOpenIteration($user, $iteration);
    }

    /**
     * Record design work inside a cycle.
     *
     * The ability nearly everything on the iteration screen is gated on — see the note above
     * the class. Changes, experiments, decisions, citations and playtest links all ask this,
     * because they are all the same question about the same window.
     */
    public function recordWork(User $user, Iteration $iteration): Response
    {
        return $this->requireOpenIteration($user, $iteration);
    }

    /**
     * Connect a cycle to a playtest that tested it.
     *
     * A named ability rather than a reuse of `recordWork`, even though it resolves identically
     * today. Attaching evidence is the one write on this screen that touches another bounded
     * context, and it is the likeliest of the group to acquire a rule of its own — so the
     * interface asks about it by name and gets a separate answer from the start.
     */
    public function attachPlaytest(User $user, Iteration $iteration): Response
    {
        return $this->requireOpenIteration($user, $iteration);
    }

    /**
     * Cut the next design version of the game from what this cycle concluded.
     *
     * The inverse condition to everything above, and the only ability in the module that
     * *requires* the iteration to be closed. A version cut from an open cycle would claim the
     * design had moved on the strength of conclusions nobody had reached — and since an open
     * cycle can still change, the claim might turn out to describe work that was abandoned.
     *
     * Write access to the game is still required, because the version is created in GameDesign
     * and this is the ability that offers the button. GameDesign checks its own rules again
     * when it runs.
     */
    public function createGameVersion(User $user, Iteration $iteration): Response
    {
        $decision = $this->requireGameWriteAccessFor($user, $iteration);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $iteration->isClosed()
            ? Response::allow()
            : Response::deny(__('Complete this iteration before cutting a new game version from it.'));
    }

    /**
     * Destroy a cycle outright.
     *
     * No route reaches this. An iteration is the record of work somebody did, and it is
     * cancelled rather than deleted so that the record of what was tried survives. The ability
     * is defined because the policy is the right place to have already decided that nobody may:
     * a design history that can be quietly removed is not a history.
     */
    public function delete(User $user, Iteration $iteration): Response
    {
        return Response::deny(__('Iterations are cancelled rather than deleted.'));
    }

    /**
     * Require that the caller may change this particular cycle.
     *
     * Two gates, in order. The game has to be open to the caller and still accepting changes;
     * then the cycle itself has to be one that can still change. A completed cycle is refused
     * rather than hidden — the caller can see it, so pretending it is gone would be a lie, and
     * the refusal carries the status's own wording so they are told which of the two endings
     * it reached.
     */
    private function requireOpenIteration(User $user, Iteration $iteration): Response
    {
        $decision = $this->requireGameWriteAccessFor($user, $iteration);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $iteration->acceptsWork()
            ? Response::allow()
            : Response::deny($iteration->status->deniedReason());
    }

    /**
     * Require write access to the game a cycle belongs to.
     */
    private function requireGameWriteAccessFor(User $user, Iteration $iteration): Response
    {
        $game = $iteration->game;

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
     * Resolve the caller's standing in the game a cycle belongs to.
     */
    private function grantFor(User $user, Iteration $iteration): GameGrant
    {
        $game = $iteration->game;

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
     * Deny in a way that does not admit the iteration exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Iteration not found.'));
    }
}
