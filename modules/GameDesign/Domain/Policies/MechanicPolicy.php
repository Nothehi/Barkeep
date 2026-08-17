<?php

namespace Modules\GameDesign\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Infrastructure\Authorization\MechanicCurators;
use Modules\Identity\Domain\Models\User;

/**
 * The single place the mechanics vocabulary is authorized.
 *
 * Two policies in this module now, because there are genuinely two questions.
 * A game is a studio's, scoped to a workspace the way everything else in
 * Barkeep is ({@see GamePolicy}); the vocabulary is the platform's, and
 * maintaining it is a platform privilege that no amount of standing inside a
 * workspace confers.
 *
 * Reading is open to every signed in account and is not conditional on
 * anything. A designer tagging their game has to be able to see the list, and
 * a vocabulary somebody has to be granted sight of is a vocabulary nobody
 * uses.
 *
 * Refusals here are 403 rather than 404, which is the opposite of the game
 * rules and is right for the same reason they are: a mechanic is public, so
 * pretending it does not exist to somebody who may not edit it would be a lie
 * they can disprove by reloading the list.
 */
class MechanicPolicy
{
    public function __construct(private readonly MechanicCurators $curators) {}

    /**
     * Read the vocabulary.
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Read one term.
     */
    public function view(User $user, Mechanic $mechanic): Response
    {
        return Response::allow();
    }

    /**
     * Add a term to the vocabulary.
     */
    public function create(User $user): Response
    {
        return $this->requireCurator($user);
    }

    /**
     * Change what a term is called or means.
     *
     * Worth being clear about the blast radius, because it is unusual for this
     * codebase: editing a mechanic changes what is displayed on every game that
     * claimed it, in every workspace. That is the intended behaviour of a
     * shared vocabulary — a definition improving should improve everywhere —
     * and it is exactly why the ability is not handed to studio owners.
     */
    public function update(User $user, Mechanic $mechanic): Response
    {
        return $this->requireCurator($user);
    }

    /**
     * Retire a term.
     */
    public function archive(User $user, Mechanic $mechanic): Response
    {
        if (! $this->requireCurator($user)->allowed()) {
            return $this->deny();
        }

        return $mechanic->isArchived()
            ? Response::deny($mechanic->status->deniedReason())
            : Response::allow();
    }

    /**
     * Delete a term outright.
     *
     * No route reaches this. A mechanic is retired rather than deleted, because
     * deleting it would silently remove a word from every game that had used it
     * to describe itself. The ability is defined because the policy is the right
     * place to have already decided that nobody may.
     */
    public function delete(User $user, Mechanic $mechanic): Response
    {
        return Response::deny(__('A mechanic is retired rather than deleted.'));
    }

    /**
     * Require that the caller curates the vocabulary.
     */
    private function requireCurator(User $user): Response
    {
        return $this->curators->includes($user)
            ? Response::allow()
            : $this->deny();
    }

    /**
     * Refuse in a way that says what is missing.
     */
    private function deny(): Response
    {
        return Response::deny(__('Only a mechanics curator may change the design vocabulary.'));
    }
}
