<?php

namespace Modules\DesignFramework\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched at the moment a framework version freezes.
 *
 * The most consequential event in the module. Before it, the version's phases
 * and content are a work in progress; after it they are immutable and games may
 * adopt them. Everything that reads a methodology keys off this.
 *
 * The phase count travels with it because a consumer deciding whether to
 * announce a new edition wants to know whether it is a real one — a version
 * published with no phases is a mistake somebody will want to hear about.
 */
final readonly class FrameworkVersionPublished
{
    public function __construct(
        public string $frameworkVersionId,
        public string $frameworkId,
        public int $versionNumber,
        public int $phaseCount,
        public string $publishedBy,
        public DateTimeImmutable $publishedAt,
    ) {}
}
