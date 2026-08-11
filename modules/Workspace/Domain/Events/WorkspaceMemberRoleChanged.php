<?php

namespace Modules\Workspace\Domain\Events;

use Modules\Workspace\Domain\Enums\WorkspaceRole;

/**
 * Dispatched when a member is promoted or demoted.
 *
 * Ownership never moves through this event — that is
 * {@see WorkspaceOwnershipTransferred}.
 */
final readonly class WorkspaceMemberRoleChanged
{
    public function __construct(
        public string $workspaceId,
        public string $userId,
        public WorkspaceRole $from,
        public WorkspaceRole $to,
        public string $changedBy,
    ) {}
}
