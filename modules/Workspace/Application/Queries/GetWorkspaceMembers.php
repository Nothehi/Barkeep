<?php

namespace Modules\Workspace\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;
use Modules\Workspace\Infrastructure\Persistence\Repositories\WorkspaceRepository;

/**
 * Everyone who belongs to a workspace, with their accounts eager loaded.
 */
final class GetWorkspaceMembers
{
    public function __construct(private readonly WorkspaceRepository $workspaces) {}

    /**
     * @return Collection<int, WorkspaceMember>
     */
    public function handle(Workspace $workspace): Collection
    {
        return $this->workspaces->membersOf($workspace);
    }
}
