<?php

namespace Modules\Workspace\Domain\Events;

use Modules\Workspace\Domain\Enums\WorkspaceRole;

/**
 * Dispatched when somebody is invited to a workspace.
 *
 * The token is deliberately absent. Delivering the invitation is Workspace's
 * own job, and an event that carried the secret would leak it to every
 * listener, queue payload and log line downstream.
 */
final readonly class WorkspaceInvitationCreated
{
    public function __construct(
        public string $workspaceId,
        public string $invitationId,
        public string $email,
        public WorkspaceRole $role,
        public string $invitedBy,
    ) {}
}
