<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a prototype is put away for good.
 *
 * The version count travels with it because it is the difference between "we
 * tried this approach once and dropped it" and "we took this through nine
 * rebuilds and then dropped it" — two events that look identical without it.
 *
 * Archiving is terminal. Nothing consuming this needs to plan for the inverse
 * event, because there is not one: a studio that picks the approach back up
 * creates a new prototype.
 */
final readonly class PrototypeArchived
{
    public function __construct(
        public string $prototypeId,
        public string $gameId,
        public int $versionCount,
        public string $archivedBy,
        public DateTimeImmutable $archivedAt,
    ) {}
}
