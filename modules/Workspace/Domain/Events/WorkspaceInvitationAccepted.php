<?php

namespace Modules\Workspace\Domain\Events;

/**
 * Dispatched when an invitation is redeemed and membership is created.
 */
final readonly class WorkspaceInvitationAccepted
{
    public function __construct(
        public string $workspaceId,
        public string $invitationId,
        public string $userId,
    ) {}
}
