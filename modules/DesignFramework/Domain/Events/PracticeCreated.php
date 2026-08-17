<?php

namespace Modules\DesignFramework\Domain\Events;

/**
 * Dispatched when a framework author adds something for designers to do.
 *
 * Practices are the content type most likely to gain automatic satisfaction
 * later — "run a two-player playtest" is a thing Playtesting can already see
 * happen — so consumers that eventually want to close the loop between the two
 * modules will start from this event and from {@see PracticeCompleted}.
 */
final readonly class PracticeCreated
{
    public function __construct(
        public string $practiceId,
        public string $frameworkVersionId,
        public ?string $phaseId,
        public string $slug,
        public string $createdBy,
    ) {}
}
