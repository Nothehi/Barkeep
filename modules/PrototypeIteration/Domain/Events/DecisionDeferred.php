<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a decision is put off rather than settled.
 *
 * The one non-terminal ending, so this is the one decision event a consumer may
 * see more than once for the same decision — deferred, taken up, deferred again.
 * That is not a defect in the lifecycle; it is what "we will look at this again
 * after the convention" looks like when it happens three times.
 */
final readonly class DecisionDeferred
{
    public function __construct(
        public string $decisionId,
        public string $iterationId,
        public string $gameId,
        public string $decidedBy,
        public DateTimeImmutable $decidedAt,
    ) {}
}
