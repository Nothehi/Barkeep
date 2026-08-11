<?php

namespace Modules\Workspace\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched when a workspace is retired.
 *
 * Archival is the end of a workspace's active life, not a deletion: consumers
 * should stop scheduling work for it but must not discard its history.
 */
final readonly class WorkspaceArchived
{
    public function __construct(
        public string $workspaceId,
        public string $archivedBy,
        public CarbonImmutable $archivedAt,
    ) {}
}
