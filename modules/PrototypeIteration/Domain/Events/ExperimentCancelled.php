<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when an experiment is abandoned.
 *
 * The honest ending for a question that stopped mattering, and distinct from
 * completion for exactly that reason: a cancelled experiment produced no result,
 * and anything that counted it as one would be reading an answer into silence.
 */
final readonly class ExperimentCancelled
{
    public function __construct(
        public string $experimentId,
        public string $iterationId,
        public string $gameId,
        public string $cancelledBy,
        public DateTimeImmutable $cancelledAt,
    ) {}
}
