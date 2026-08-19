<?php

namespace Modules\PrototypeIteration\Domain\Events;

/**
 * Dispatched when a prototype is rebuilt into a new state.
 *
 * The number is carried rather than left to be looked up, because "v2" and
 * "v11" say entirely different things about how much a studio has reworked
 * something, and a consumer that had to count the rows to find out would be
 * reaching into this module's tables to do it.
 */
final readonly class PrototypeVersionCreated
{
    public function __construct(
        public string $prototypeVersionId,
        public string $prototypeId,
        public string $gameId,
        public int $versionNumber,
        public string $createdBy,
    ) {}
}
