<?php

namespace Modules\PrototypeIteration\Domain\Events;

use DateTimeImmutable;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;

/**
 * Dispatched when a designer closes a turn of the design loop.
 *
 * The richest event in the module, and deliberately so. This is the moment the
 * loop the platform exists to support closes, and the counts travelling with it
 * are what let a consumer tell a substantial cycle from a nominal one without
 * reaching into this module's tables: four changes, two experiments, three
 * playtests and an accepted decision is a different event from one change and no
 * evidence, whatever the outcome says.
 *
 * The outcome is carried as the enum rather than as a string so a consumer
 * cannot invent a fifth value by typo.
 *
 * What this event explicitly does *not* do is create the next game version.
 * That is the designer's decision, taken deliberately afterwards through
 * GameDesign — see section 30. A listener here that cut a version would take
 * that judgement away from them.
 */
final readonly class IterationCompleted
{
    public function __construct(
        public string $iterationId,
        public string $gameId,
        public string $gameVersionId,
        public string $prototypeVersionId,
        public IterationOutcome $outcome,
        public string $completedBy,
        public DateTimeImmutable $completedAt,
        public int $changeCount,
        public int $experimentCount,
        public int $decisionCount,
        public int $playtestCount,
    ) {}
}
