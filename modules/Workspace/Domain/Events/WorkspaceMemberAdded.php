<?php

namespace Modules\Workspace\Domain\Events;

use Modules\Workspace\Domain\Enums\WorkspaceRole;

/**
 * Dispatched when an account gains membership of a workspace.
 *
 * Covers both routes in: an administrator adding somebody directly, and
 * somebody redeeming an invitation.
 */
final readonly class WorkspaceMemberAdded
{
    public function __construct(
        public string $workspaceId,
        public string $memberId,
        public string $userId,
        public WorkspaceRole $role,
    ) {}
}
