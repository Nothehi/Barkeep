<?php

namespace Modules\Workspace\Application\Queries;

use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;
use Modules\Workspace\Infrastructure\Persistence\Repositories\WorkspaceRepository;

/**
 * Resolve a workspace by its address.
 *
 * Resolution is unauthorized on purpose: finding the workspace and deciding
 * who may see it are separate steps, and every caller runs the policy on the
 * result. Merging the two would make it easy to forget the second half.
 */
final class GetWorkspace
{
    public function __construct(private readonly WorkspaceRepository $workspaces) {}

    public function handle(WorkspaceSlug $slug): ?Workspace
    {
        return $this->workspaces->findBySlug($slug);
    }
}
