<?php

namespace Modules\Workspace\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * The single place workspace access is decided.
 *
 * Every answer is derived from the same four inputs — the acting account, its
 * membership, that membership's role and the workspace's status — so no
 * controller ever has to compare an owner id itself.
 *
 * Two kinds of "no" are returned deliberately:
 *
 * - a non-member gets a 404, so slugs cannot be enumerated to discover which
 *   workspaces exist;
 * - a member without the necessary role gets a 403, because they already know
 *   the workspace exists and hiding it would only be confusing.
 *
 * The policy holds no state of its own. The gate builds a fresh instance per
 * check, and the repeated membership lookups are absorbed by the workspace
 * model instead — see `Workspace::memberFor()`.
 */
class WorkspacePolicy
{
    /**
     * Anyone signed in has a workspace list; it may simply be empty.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone signed in may start a workspace of their own.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Read the workspace and anything scoped to it.
     */
    public function view(User $user, Workspace $workspace): Response
    {
        if (! $workspace->status->isReadable()) {
            return $this->hide();
        }

        return $this->member($user, $workspace) === null
            ? $this->hide()
            : Response::allow();
    }

    /**
     * Change the workspace's own settings.
     */
    public function update(User $user, Workspace $workspace): Response
    {
        return $this->requireRole($user, $workspace, WorkspaceRole::Admin);
    }

    /**
     * Retire the workspace.
     *
     * Archival ends the workspace's active life, so it stays with the owner
     * rather than with anyone who happens to administer it.
     */
    public function archive(User $user, Workspace $workspace): Response
    {
        return $this->requireRole($user, $workspace, WorkspaceRole::Owner);
    }

    /**
     * See who belongs to the workspace.
     */
    public function viewMembers(User $user, Workspace $workspace): Response
    {
        return $this->view($user, $workspace);
    }

    /**
     * Administer the workspace's membership at all.
     */
    public function manageMembers(User $user, Workspace $workspace): Response
    {
        return $this->requireRole($user, $workspace, WorkspaceRole::Admin);
    }

    /**
     * Invite somebody new.
     */
    public function inviteMembers(User $user, Workspace $workspace): Response
    {
        return $this->requireRole($user, $workspace, WorkspaceRole::Admin);
    }

    /**
     * See and withdraw the workspace's outstanding invitations.
     */
    public function manageInvitations(User $user, Workspace $workspace): Response
    {
        return $this->requireRole($user, $workspace, WorkspaceRole::Admin);
    }

    /**
     * Remove a specific member.
     *
     * Administrators may only remove people below them, which stops two
     * administrators from being able to eject each other, and nobody may
     * remove the owner — ownership has to move first.
     */
    public function removeMembers(User $user, Workspace $workspace, ?WorkspaceMember $member = null): Response
    {
        $decision = $this->requireRole($user, $workspace, WorkspaceRole::Admin);

        if (! $decision->allowed() || $member === null) {
            return $decision;
        }

        if ($member->isOwner()) {
            return Response::deny(__('The workspace owner cannot be removed.'));
        }

        $actor = $this->member($user, $workspace);

        if ($actor === null || $actor->is($member)) {
            return Response::deny(__('Leave the workspace instead of removing yourself.'));
        }

        return $actor->role->outranks($member->role) || $actor->isOwner()
            ? Response::allow()
            : Response::deny(__('You may only remove members below your own role.'));
    }

    /**
     * Promote or demote a member.
     *
     * Reserved to the owner: letting administrators promote each other would
     * make the role meaningless, and demoting the owner is only ever allowed
     * as part of an ownership transfer.
     */
    public function changeMemberRole(User $user, Workspace $workspace, ?WorkspaceMember $member = null): Response
    {
        $decision = $this->requireRole($user, $workspace, WorkspaceRole::Owner);

        if (! $decision->allowed() || $member === null) {
            return $decision;
        }

        return $member->isOwner()
            ? Response::deny(__('The owner\'s role can only be changed by transferring ownership.'))
            : Response::allow();
    }

    /**
     * Hand the workspace to somebody else.
     */
    public function transferOwnership(User $user, Workspace $workspace): Response
    {
        return $this->requireRole($user, $workspace, WorkspaceRole::Owner);
    }

    /**
     * Walk away from the workspace.
     *
     * The owner cannot: a workspace without an owner has no one who can
     * archive it or manage its members.
     */
    public function leave(User $user, Workspace $workspace): Response
    {
        $member = $this->member($user, $workspace);

        if ($member === null || ! $workspace->status->isReadable()) {
            return $this->hide();
        }

        return $member->isOwner()
            ? Response::deny(__('Transfer ownership before leaving the workspace.'))
            : Response::allow();
    }

    /**
     * Require at least the given role on a workspace that may still change.
     */
    private function requireRole(User $user, Workspace $workspace, WorkspaceRole $minimum): Response
    {
        $member = $this->member($user, $workspace);

        if ($member === null || ! $workspace->status->isReadable()) {
            return $this->hide();
        }

        if (! $workspace->isModifiable()) {
            return Response::deny($workspace->status->deniedReason());
        }

        return $member->role->atLeast($minimum)
            ? Response::allow()
            : Response::deny(__('Your role in this workspace does not allow that.'));
    }

    /**
     * Resolve the acting account's membership.
     */
    private function member(User $user, Workspace $workspace): ?WorkspaceMember
    {
        return $workspace->memberFor($user);
    }

    /**
     * Deny in a way that does not admit the workspace exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Workspace not found.'));
    }
}
