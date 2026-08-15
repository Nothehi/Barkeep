<?php

namespace Modules\Workspace\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Infrastructure\Persistence\Repositories\WorkspaceRepository;

/**
 * The workspaces an account may switch between.
 *
 * Scoped to membership, never to "all workspaces". This is the only list the
 * client is ever given, so the workspace switcher cannot offer a workspace the
 * person does not belong to in the first place.
 *
 * @see WorkspaceRepository::forUser()
 */
final class GetUserWorkspaces
{
    public function __construct(private readonly WorkspaceRepository $workspaces) {}

    /**
     * @return Collection<int, Workspace>
     */
    public function handle(User $user): Collection
    {
        return $this->workspaces->forUser($user);
    }
}
