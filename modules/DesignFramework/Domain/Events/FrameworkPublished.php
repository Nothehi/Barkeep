<?php

namespace Modules\DesignFramework\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a framework becomes visible to everybody on the platform.
 *
 * Distinct from {@see FrameworkVersionPublished}, which is the event that
 * matters to designers. This one says the methodology exists; that one says
 * there is an edition of it a game can follow.
 */
final readonly class FrameworkPublished
{
    public function __construct(
        public string $frameworkId,
        public string $slug,
        public string $publishedBy,
        public DateTimeImmutable $publishedAt,
    ) {}
}
