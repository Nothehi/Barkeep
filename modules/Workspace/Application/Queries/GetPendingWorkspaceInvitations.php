<?php

namespace Modules\Workspace\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Infrastructure\Persistence\Repositories\WorkspaceRepository;

/**
 * A workspace's invitations that can still be redeemed.
 *
 * Invitations that have quietly passed their expiry are left out, so the
 * members screen never offers to revoke something that is already dead.
 */
final class GetPendingWorkspaceInvitations
{
    public function __construct(private readonly WorkspaceRepository $workspaces) {}

    /**
     * @return Collection<int, WorkspaceInvitation>
     */
    public function handle(Workspace $workspace): Collection
    {
        return $this->workspaces->pendingInvitationsFor($workspace);
    }
}
