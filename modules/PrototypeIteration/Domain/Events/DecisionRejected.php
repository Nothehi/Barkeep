<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a proposed conclusion is refused.
 *
 * Kept as a first-class event rather than folded into a generic
 * "decision settled", because a rejected proposal is a real part of the design
 * record: the studio considered doing this and decided against it, and the
 * reason on the record is often more useful later than the reasons behind the
 * things they did do.
 */
final readonly class DecisionRejected
{
    public function __construct(
        public string $decisionId,
        public string $iterationId,
        public string $gameId,
        public int $evidenceCount,
        public string $decidedBy,
        public DateTimeImmutable $decidedAt,
    ) {}
}
