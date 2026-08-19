<?php

namespace Modules\PrototypeIteration\Domain\Events;

use Modules\PrototypeIteration\Domain\Enums\PrototypeType;

/**
 * Dispatched when a designer starts building something testable.
 *
 * The kind travels with it because it is the field that makes the event mean
 * different things to different consumers: a paper prototype and a 3D printed
 * one represent very different commitments of time and money, and anything that
 * eventually reasons about a studio's pace needs to know which it is looking at
 * without going back to the table for it.
 */
final readonly class PrototypeCreated
{
    public function __construct(
        public string $prototypeId,
        public string $gameId,
        public string $gameVersionId,
        public PrototypeType $type,
        public string $createdBy,
    ) {}
}
