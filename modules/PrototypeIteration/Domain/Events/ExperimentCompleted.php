<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when an experiment produces a result.
 *
 * Carries whether a conclusion was drawn, rather than the conclusion itself.
 * The distinction matters: an experiment with a result and no conclusion is one
 * where the studio saw what happened and has not yet decided what it means,
 * which is a real and common state — and a consumer can act on knowing that
 * without needing the prose.
 *
 * Note that this fires only when somebody explicitly completes the experiment.
 * Completing the iteration around it does not cascade; an experiment still
 * running when the cycle closed stayed unanswered, and saying otherwise would
 * put a result in the record that nobody observed.
 */
final readonly class ExperimentCompleted
{
    public function __construct(
        public string $experimentId,
        public string $iterationId,
        public string $gameId,
        public bool $hasConclusion,
        public string $completedBy,
        public DateTimeImmutable $completedAt,
    ) {}
}
