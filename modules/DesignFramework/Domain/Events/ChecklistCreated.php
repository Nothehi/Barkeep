<?php

namespace Modules\DesignFramework\Domain\Events;

/**
 * Dispatched when a framework author adds a readiness gate.
 *
 * The items come afterwards and do not each announce themselves: a checklist is
 * authored as a whole, and an event per item would be noise nobody consumes.
 */
final readonly class ChecklistCreated
{
    public function __construct(
        public string $checklistId,
        public string $frameworkVersionId,
        public ?string $phaseId,
        public string $slug,
        public string $createdBy,
    ) {}
}
