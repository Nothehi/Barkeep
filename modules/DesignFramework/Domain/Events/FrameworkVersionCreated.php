<?php

namespace Modules\DesignFramework\Domain\Events;

/**
 * Dispatched when a new edition of a framework is opened for writing.
 *
 * Carries the number rather than only the id, because "v2 exists" is the fact a
 * consumer acts on — a notification telling designers on v1 that there is
 * something newer, for instance, once notifications exist.
 */
final readonly class FrameworkVersionCreated
{
    public function __construct(
        public string $frameworkVersionId,
        public string $frameworkId,
        public int $versionNumber,
        public string $createdBy,
    ) {}
}
