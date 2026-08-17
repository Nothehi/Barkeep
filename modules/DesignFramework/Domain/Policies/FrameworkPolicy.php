<?php

namespace Modules\DesignFramework\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Authorization\FrameworkAdministrators;
use Modules\Identity\Domain\Models\User;

/**
 * The single place framework administration is decided.
 *
 * Frameworks are the one thing in Barkeep that is not owned by a workspace, and
 * that shapes every answer here. There is no membership to check and no game to
 * scope against; there are two populations:
 *
 * - every signed in account may *read* published and archived frameworks. A
 *   methodology exists to be followed, and hiding it from the designers it is for
 *   would be absurd.
 * - a small configured set of accounts may write them. See
 *   {@see FrameworkAdministrators} for why that is a configuration list today and
 *   what replaces it.
 *
 * Drafts sit outside that split: they are visible only to administrators, because
 * a half-written methodology visible to every designer would be read as advice
 * and it is not advice yet.
 *
 * Two kinds of "no" are returned, matching the convention the other contexts
 * follow:
 *
 * - somebody who may not see a draft gets a 404, because telling them a draft
 *   exists is itself a disclosure about work in progress;
 * - somebody who can see a framework but may not change it gets a 403, because
 *   they already know it exists and hiding it would only confuse them.
 *
 * What this policy deliberately does *not* decide is whether a published version
 * may be edited. That is a domain invariant rather than a permission — no account,
 * however privileged, may rewrite a version games are already following — so it
 * lives in `FrameworkVersionGuard` and is enforced on every command. The abilities
 * here fold it in as well, so the interface does not offer buttons the domain will
 * refuse, but the guard is what makes it true.
 */
class FrameworkPolicy
{
    public function __construct(private readonly FrameworkAdministrators $administrators) {}

    /**
     * See the list of frameworks.
     *
     * Open to every signed in account. The list itself is filtered — drafts are
     * only included for administrators — which is the query's job rather than
     * this one's.
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Read a framework.
     *
     * A draft is hidden from everybody but an administrator, and hidden rather
     * than refused: its address is the only thing that leaks, and a 403 would
     * confirm the address names something.
     */
    public function view(User $user, Framework $framework): Response
    {
        if ($framework->status->isPubliclyVisible()) {
            return Response::allow();
        }

        return $this->administers($user)
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Start writing a new methodology.
     */
    public function create(User $user): Response
    {
        return $this->requireAdministrator($user);
    }

    /**
     * Change a framework's name, address or description.
     *
     * Refused once the framework is published, because those three fields are
     * what games and prose cite. The framework's *versions* are a different
     * matter — a published framework happily gains new drafts, which is how a
     * methodology evolves at all.
     */
    public function update(User $user, Framework $framework): Response
    {
        $decision = $this->requireAdministrator($user);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $framework->isModifiable()
            ? Response::allow()
            : Response::deny($framework->status->deniedReason());
    }

    /**
     * Make a framework visible to every designer on the platform.
     */
    public function publish(User $user, Framework $framework): Response
    {
        return $this->requireTransition($user, $framework->status->canTransitionTo(FrameworkStatus::Published), $framework);
    }

    /**
     * Retire a framework.
     *
     * Terminal, and it does not take anybody's work with it: games following its
     * versions keep reading them. What it stops is anything new — no further
     * versions, no further adoptions.
     */
    public function archive(User $user, Framework $framework): Response
    {
        return $this->requireTransition($user, $framework->status->canTransitionTo(FrameworkStatus::Archived), $framework);
    }

    /**
     * Open a new edition of a framework.
     *
     * Allowed on a published framework and refused on an archived one. This is
     * the ability that makes versioning usable: the only way to change a
     * published methodology is to create the next version of it.
     */
    public function createVersion(User $user, Framework $framework): Response
    {
        $decision = $this->requireAdministrator($user);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $framework->acceptsNewVersions()
            ? Response::allow()
            : Response::deny($framework->status->deniedReason());
    }

    /**
     * Read one edition of a framework, and its content.
     *
     * A draft version is hidden from non-administrators for the same reason a
     * draft framework is. A published version inside a published framework is
     * open to everybody, which is what lets a designer read the methodology
     * before adopting it.
     */
    public function viewVersion(User $user, FrameworkVersion $version): Response
    {
        if ($this->administers($user)) {
            return Response::allow();
        }

        $framework = $version->framework;

        $visible = $version->status->isPubliclyVisible()
            && $framework !== null
            && $framework->status->isPubliclyVisible();

        return $visible ? Response::allow() : $this->hide();
    }

    /**
     * Change a version, or anything inside it.
     *
     * The single ability behind the whole framework builder: adding a phase,
     * renaming a criterion, reordering checklist items and editing the version's
     * own description all ask this. Concentrating them is deliberate — the rule
     * they share is "the version is still a draft", and splitting it into nine
     * abilities would be nine chances for one of them to forget.
     *
     * Refused for everybody once the version is published. That is not a
     * permission the product could grant to somebody more senior; it is the
     * invariant that makes a game's recorded answers mean anything.
     */
    public function updateVersion(User $user, FrameworkVersion $version): Response
    {
        $decision = $this->requireAdministrator($user);

        if (! $decision->allowed()) {
            return $decision;
        }

        if (! $version->isModifiable()) {
            return Response::deny($version->status->deniedReason());
        }

        $framework = $version->framework;

        return $framework === null || $framework->acceptsNewVersions()
            ? Response::allow()
            : Response::deny($framework->status->deniedReason());
    }

    /**
     * Freeze a version and release it.
     */
    public function publishVersion(User $user, FrameworkVersion $version): Response
    {
        return $this->requireVersionTransition($user, $version, FrameworkStatus::Published);
    }

    /**
     * Retire a version.
     */
    public function archiveVersion(User $user, FrameworkVersion $version): Response
    {
        return $this->requireVersionTransition($user, $version, FrameworkStatus::Archived);
    }

    /**
     * Destroy a framework outright.
     *
     * No route reaches this. Frameworks are archived rather than deleted, because
     * games follow their versions and the database refuses to remove a version
     * anything has adopted. The ability is defined because the policy is the right
     * place to have already decided that nobody may.
     */
    public function delete(User $user, Framework $framework): Response
    {
        return Response::deny(__('Frameworks are archived rather than deleted.'));
    }

    /**
     * Require that the caller administers frameworks and that the move is legal.
     */
    private function requireTransition(User $user, bool $legal, Framework $framework): Response
    {
        $decision = $this->requireAdministrator($user);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $legal
            ? Response::allow()
            : Response::deny($framework->status->deniedReason());
    }

    /**
     * Require that the caller administers frameworks and that the version may move.
     */
    private function requireVersionTransition(
        User $user,
        FrameworkVersion $version,
        FrameworkStatus $target,
    ): Response {
        $decision = $this->requireAdministrator($user);

        if (! $decision->allowed()) {
            return $decision;
        }

        return $version->status->canTransitionTo($target)
            ? Response::allow()
            : Response::deny($version->status->deniedReason());
    }

    /**
     * Require that the caller is a framework administrator.
     *
     * The refusal explains itself when nobody is configured at all, because that
     * is a setup problem rather than a permission problem and a bare 403 would
     * send whoever is installing Barkeep looking in the wrong place.
     */
    private function requireAdministrator(User $user): Response
    {
        if ($this->administers($user)) {
            return Response::allow();
        }

        return $this->administrators->anyConfigured()
            ? Response::deny(__('Only a framework administrator can change design frameworks.'))
            : Response::deny(__('No framework administrators are configured for this installation.'));
    }

    /**
     * Determine whether the account administers frameworks.
     */
    private function administers(User $user): bool
    {
        return $this->administrators->includes($user);
    }

    /**
     * Deny in a way that does not admit the framework exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Framework not found.'));
    }
}
