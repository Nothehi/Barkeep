<?php

namespace Modules\PrototypeIteration\Domain\Events;

/**
 * Dispatched when a designer proposes a conclusion.
 *
 * Proposal and acceptance are separate events because the gap between them is
 * where the argument happens, and a studio where every decision is accepted the
 * moment it is proposed is doing something different from one where they sit for
 * a week. Neither is wrong; conflating them loses the difference.
 */
final readonly class DecisionCreated
{
    public function __construct(
        public string $decisionId,
        public string $iterationId,
        public string $gameId,
        public string $createdBy,
    ) {}
}
