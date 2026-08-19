<?php

namespace Modules\PrototypeIteration\Domain\Events;

use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;

/**
 * Dispatched when a deliberate modification is recorded.
 *
 * The category travels with it because it is what makes a stream of these
 * readable in aggregate — "this project's last month was all pacing and
 * economy" is a sentence a consumer can form from the category alone, and
 * cannot form from the ids.
 *
 * The reason does not travel. It is the most valuable field on the record and
 * the one least suited to an event: it is prose written for a human reader, and
 * a consumer that wanted it should read the change rather than have every
 * listener carry a copy.
 */
final readonly class DesignChangeCreated
{
    public function __construct(
        public string $changeId,
        public string $iterationId,
        public string $gameId,
        public DesignChangeCategory $category,
        public string $createdBy,
    ) {}
}
