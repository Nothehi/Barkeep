<?php

namespace Modules\Workspace\Domain\Exceptions;

use Modules\Workspace\Domain\Enums\WorkspaceStatus;

/**
 * Raised when a workspace that is not active is asked to change.
 */
final class WorkspaceIsNotActive extends WorkspaceRuleViolation
{
    private function __construct(public readonly WorkspaceStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(WorkspaceStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    public function status(): int
    {
        return 409;
    }
}
