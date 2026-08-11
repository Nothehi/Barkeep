<?php

namespace Modules\Workspace\Domain\Events;

/**
 * Dispatched when a workspace's own settings change.
 *
 * Membership and ownership changes have their own events; this one is only
 * about the workspace's name, address and description.
 */
final readonly class WorkspaceUpdated
{
    /**
     * @param  list<string>  $changed  The attributes that actually changed.
     */
    public function __construct(
        public string $workspaceId,
        public string $updatedBy,
        public array $changed,
    ) {}
}
