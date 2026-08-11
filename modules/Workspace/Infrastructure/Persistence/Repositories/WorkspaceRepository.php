<?php

namespace Modules\Workspace\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\Models\WorkspaceMember;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;

/**
 * Every read the module performs against its own tables.
 *
 * Collecting them here is what makes the "workspaces are only visible to
 * their members" rule checkable: there is one method that answers "which
 * workspaces may this account see?", and no query elsewhere has the chance to
 * forget the membership join.
 */
final class WorkspaceRepository
{
    /**
     * The workspaces the given account belongs to.
     *
     * Scoped by membership rather than by ownership, so a workspace somebody
     * was invited into is listed alongside the ones they created. Suspended
     * workspaces are withheld entirely.
     *
     * @return Collection<int, Workspace>
     */
    public function forUser(User $user): Collection
    {
        $workspaces = Workspace::query()
            ->whereHas('members', fn ($members) => $members->where('user_id', $user->id))
            ->where('status', '!=', WorkspaceStatus::Suspended)
            ->withCount('members')
            ->orderBy('name')
            ->get();

        return $this->withMembershipsFor($user, $workspaces);
    }

    /**
     * Attach the account's own membership to each workspace up front.
     *
     * Every workspace is rendered with what the caller may do in it, and each
     * of those answers needs their role. This list is shared with every
     * Inertia page, so resolving the roles lazily would put a query per
     * workspace on every page load; one query for all of them does instead.
     *
     * @param  Collection<int, Workspace>  $workspaces
     * @return Collection<int, Workspace>
     */
    private function withMembershipsFor(User $user, Collection $workspaces): Collection
    {
        if ($workspaces->isEmpty()) {
            return $workspaces;
        }

        $memberships = WorkspaceMember::query()
            ->where('user_id', $user->id)
            ->whereIn('workspace_id', $workspaces->modelKeys())
            ->get()
            ->keyBy('workspace_id');

        return $workspaces->each(
            fn (Workspace $workspace) => $workspace->rememberMembership(
                $user,
                $memberships->get($workspace->getKey()),
            ),
        );
    }

    /**
     * Find a workspace by its address.
     *
     * Deliberately unscoped: resolving a workspace and deciding who may see it
     * are separate steps, and authorization runs on the result.
     */
    public function findBySlug(WorkspaceSlug $slug): ?Workspace
    {
        return Workspace::query()
            ->where('slug', $slug->value)
            ->first();
    }

    /**
     * Determine whether an address is already in use.
     *
     * @param  string|null  $exceptWorkspaceId  the workspace allowed to keep its own address
     */
    public function slugExists(WorkspaceSlug $slug, ?string $exceptWorkspaceId = null): bool
    {
        return Workspace::query()
            ->where('slug', $slug->value)
            ->when($exceptWorkspaceId !== null, fn ($query) => $query->whereKeyNot($exceptWorkspaceId))
            ->exists();
    }

    /**
     * A workspace's members, ordered so the people who run it come first.
     *
     * @return Collection<int, WorkspaceMember>
     */
    public function membersOf(Workspace $workspace): Collection
    {
        return $workspace->members()
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('joined_at')
            ->get();
    }

    /**
     * A workspace's outstanding invitations.
     *
     * Invitations that have quietly passed their expiry are still stored as
     * pending, so they are filtered out here rather than being shown as
     * actionable.
     *
     * @return Collection<int, WorkspaceInvitation>
     */
    public function pendingInvitationsFor(Workspace $workspace): Collection
    {
        return $workspace->invitations()
            ->pending()
            ->where('expires_at', '>', now())
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Resolve the invitation a token refers to.
     *
     * Matching is by digest, so an invalid token is indistinguishable from an
     * unknown one: both simply return null.
     */
    public function findInvitationByToken(InvitationToken $token): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::query()
            ->forToken($token)
            ->with('workspace')
            ->first();
    }
}
