<?php

namespace Modules\Workspace\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched once a workspace and its owner membership both exist.
 *
 * Carries ids only. Consumers that need the workspace itself resolve it
 * through Workspace's own query layer rather than reaching into its tables.
 */
final readonly class WorkspaceCreated
{
    public function __construct(
        public string $workspaceId,
        public string $ownerId,
        public string $slug,
        public CarbonImmutable $createdAt,
    ) {}
}
