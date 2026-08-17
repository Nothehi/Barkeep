<?php

namespace Modules\DesignFramework\Domain\Events;

/**
 * Dispatched when somebody starts writing a new methodology.
 *
 * Carries the address as well as the id, because a framework is cited by
 * address in URLs and in prose, and a consumer announcing "the Board Game
 * Design Framework is being written" wants the thing people recognise.
 */
final readonly class FrameworkCreated
{
    public function __construct(
        public string $frameworkId,
        public string $slug,
        public string $name,
        public string $createdBy,
    ) {}
}
