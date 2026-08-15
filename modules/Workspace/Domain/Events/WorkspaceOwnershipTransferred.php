<?php

namespace Modules\Workspace\Domain\Events;

use Modules\Workspace\Domain\Enums\WorkspaceRole;

/**
 * Dispatched when a workspace changes hands.
 *
 * Both sides of the swap are reported, because a consumer that tracks "what
 * do I own?" has to learn about the loss as well as the gain.
 */
final readonly class WorkspaceOwnershipTransferred
{
    public function __construct(
        public string $workspaceId,
        public string $previousOwnerId,
        public string $newOwnerId,
        public WorkspaceRole $previousOwnerRole,
    ) {}
}
