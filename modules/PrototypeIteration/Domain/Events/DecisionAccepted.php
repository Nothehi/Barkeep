<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a proposed conclusion is agreed.
 *
 * The most consequential event in the module. An accepted decision is the
 * sentence the design will be built on, and it is terminal — there is no
 * `DecisionUnaccepted` to plan for, because reversal is a new decision in a
 * later iteration rather than an edit to this one.
 *
 * The evidence count travels with it because "agreed, citing four playtests"
 * and "agreed, citing nothing" are different events to anything that eventually
 * reasons about how a studio decides, and counting the citations would mean
 * reaching into this module's tables.
 */
final readonly class DecisionAccepted
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
