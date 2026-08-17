<?php

namespace Modules\DesignFramework\Domain\Events;

/**
 * Dispatched when a framework author adds something for designers to assess.
 *
 * The phase is nullable because a criterion may apply to the whole methodology
 * rather than to one stage of it.
 */
final readonly class CriterionCreated
{
    public function __construct(
        public string $criterionId,
        public string $frameworkVersionId,
        public ?string $phaseId,
        public string $slug,
        public string $createdBy,
    ) {}
}
