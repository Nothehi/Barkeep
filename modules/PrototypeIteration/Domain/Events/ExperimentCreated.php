<?php

namespace Modules\PrototypeIteration\Domain\Events;

/**
 * Dispatched when a designer writes down a question they intend to answer.
 *
 * Worth an event of its own, before anything has been run, because framing the
 * question in advance is the practice the module is trying to encourage — and
 * anything that eventually recognises good method needs to be able to see that
 * the question predated the result.
 */
final readonly class ExperimentCreated
{
    public function __construct(
        public string $experimentId,
        public string $iterationId,
        public string $gameId,
        public string $createdBy,
    ) {}
}
