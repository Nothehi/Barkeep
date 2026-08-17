<?php

namespace Modules\DesignFramework\Domain\Events;

/**
 * Dispatched when a stage is added to a framework version's arc.
 *
 * Only ever fires against a draft version — published versions are frozen — so a
 * consumer can treat this as "the methodology is being authored" rather than as
 * something a designer following the framework needs to know about.
 */
final readonly class PhaseCreated
{
    public function __construct(
        public string $phaseId,
        public string $frameworkVersionId,
        public string $slug,
        public int $position,
        public string $createdBy,
    ) {}
}
