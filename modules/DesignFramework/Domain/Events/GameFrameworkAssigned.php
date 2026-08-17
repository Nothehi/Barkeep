<?php

namespace Modules\DesignFramework\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a game takes up a methodology.
 *
 * The event that connects the two halves of the product, and the one a
 * gamification or analytics context will care about most: a studio that adopts a
 * framework has committed to a process, which is a much stronger signal than
 * having created a game.
 *
 * Carries the framework as well as the version so a consumer does not have to
 * read this module's tables to find out which methodology was adopted. The
 * version number is here for the same reason — "adopted v1" is the sentence, and
 * it stays true after v2 ships.
 */
final readonly class GameFrameworkAssigned
{
    public function __construct(
        public string $gameFrameworkId,
        public string $gameId,
        public string $frameworkId,
        public string $frameworkVersionId,
        public int $versionNumber,
        public string $adoptedBy,
        public DateTimeImmutable $startedAt,
    ) {}
}
