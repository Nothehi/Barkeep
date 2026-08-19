<?php

namespace Modules\PrototypeIteration\Domain\Events;

/**
 * Dispatched when a designer plans a turn of the design loop.
 *
 * All three references travel with it — the game, the design state and the built
 * state — because that triple is what an iteration *is*, and a consumer holding
 * only the iteration id would have to come back into this module to learn what
 * the cycle was even about.
 */
final readonly class IterationCreated
{
    public function __construct(
        public string $iterationId,
        public string $gameId,
        public string $gameVersionId,
        public string $prototypeVersionId,
        public string $createdBy,
    ) {}
}
