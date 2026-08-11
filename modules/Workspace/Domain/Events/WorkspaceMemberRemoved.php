<?php

namespace Modules\Workspace\Domain\Events;

/**
 * Dispatched when an account loses membership of a workspace.
 *
 * The distinction between being removed and leaving is carried by
 * {@see self::$removedBy}, which equals the member's own account id when they
 * left of their own accord.
 */
final readonly class WorkspaceMemberRemoved
{
    public function __construct(
        public string $workspaceId,
        public string $userId,
        public string $removedBy,
    ) {}

    /**
     * Determine whether the member left rather than being removed.
     */
    public function wasVoluntary(): bool
    {
        return $this->userId === $this->removedBy;
    }
}
