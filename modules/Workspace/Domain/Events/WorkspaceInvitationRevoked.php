<?php

namespace Modules\Workspace\Domain\Events;

/**
 * Dispatched when a pending invitation is withdrawn before it is redeemed.
 */
final readonly class WorkspaceInvitationRevoked
{
    public function __construct(
        public string $workspaceId,
        public string $invitationId,
        public string $email,
        public string $revokedBy,
    ) {}
}
