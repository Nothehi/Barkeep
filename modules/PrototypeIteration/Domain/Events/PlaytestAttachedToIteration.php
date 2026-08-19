<?php

namespace Modules\PrototypeIteration\Domain\Events;

/**
 * Dispatched when an iteration is connected to the playtest that tested it.
 *
 * The seam between this module and Playtesting, announced. Both ids travel
 * because a consumer will almost always be interested in one side or the other:
 * something watching design work wants the iteration, something watching
 * evidence wants the playtest, and neither should have to join through this
 * module's tables to find its counterpart.
 *
 * There is no detachment event. Removing a link corrects a mistake in
 * bookkeeping — somebody attached the wrong playtest — rather than recording
 * that something happened, and an event announcing it would invite consumers to
 * treat an accounting fix as a design decision.
 */
final readonly class PlaytestAttachedToIteration
{
    public function __construct(
        public string $linkId,
        public string $iterationId,
        public string $playtestId,
        public string $gameId,
        public string $attachedBy,
    ) {}
}
